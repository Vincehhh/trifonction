<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
use Michelf\Markdown;


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentification requise']);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

require_once 'config_strava.php';
require_once 'db_connect.php';
require_once 'config_gemini.php';
require_once 'helpers.php';

$user_id = intval($_SESSION['user_id']);
$response_data = ['success' => false, 'error' => '', 'message' => ''];

try {

    $sport = isset($_POST['sport']) ? strtolower(trim($_POST['sport'])) : '';
    
    if (empty($sport)) {
        throw new Exception('Le paramètre "sport" est requis (swim ou run)');
    }

    if (!in_array($sport, ['swim', 'run'])) {
        throw new Exception('Sport invalide. Seuls "swim" et "run" sont acceptés.');
    }

    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    $goals = isset($_POST['goals']) ? trim($_POST['goals']) : '';

    if (empty($experience)) {
        throw new Exception('Le niveau d\'expérience est requis');
    }


    $valid_experience = ['debutant', 'intermediaire', 'avance', 'expert'];
    $valid_distance = ['short', 'medium', 'long', 'ultra'];
    $valid_pool = ['25m', '50m', 'eau-libre'];

    if (!in_array($experience, $valid_experience)) {
        throw new Exception('Niveau de natation invalide');
    }
    if (!in_array($swim_distance, $valid_distance)) {
        throw new Exception('Distance objectif invalide');
    }
    if (!in_array($pool_size, $valid_pool)) {
        throw new Exception('Taille du bassin invalide');
    }


    if (strlen($goals) > 500) {
        throw new Exception('Les paramètres de mission doivent faire moins de 500 caractères');
    }

    $quota_result = checkAndUpdateQuota($pdo, $user_id);
    
    if (!$quota_result['allowed']) {
        throw new Exception($quota_result['message']);
    }

 
    $stats = getStravaStats($pdo, $user_id);
    $activitiesList = getStravaActivities($pdo, $user_id, 20);

    if (empty($stats) && empty($activitiesList)) {
        throw new Exception('Données Strava indisponibles. Assurez-vous que votre compte est connecté.');
    }


    $gemini_response = askGeminiCoachSwim(
        $stats,
        $activitiesList,
        $experience,
        $swim_distance,
        $pool_size,
        $goals
    );

    if (strpos($gemini_response, 'Erreur') !== false && strpos($gemini_response, 'Quota') !== false) {
        throw new Exception('Quota API Gemini atteint. Réessayez dans quelques heures.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO generated_plans (user_id, sport, level_input, goal_input, generated_result, created_at)
        VALUES (:user_id, 'swim', :level, :goals, :result, NOW())
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':level' => $experience,
        ':goals' => $goals,
        ':result' => json_encode([
            'pool_size' => $pool_size,
            'swim_distance' => $swim_distance,
            'experience' => $experience,
            'response' => $gemini_response,
            'timestamp' => date('Y-m-d H:i:s')
        ])
    ]);

    $response_data['success'] = true;
    $response_data['message'] = $gemini_response;
    $response_data['quota_remaining'] = $quota_result['remaining'];

    http_response_code(200);

} catch (Exception $e) {
    http_response_code(400);
    $response_data['error'] = $e->getMessage();
}

echo json_encode($response_data, 64);
exit();


function checkAndUpdateQuota($pdo, $user_id) {
    $max_calls_per_day = 20;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM generated_plans
            WHERE user_id = :user_id 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch();
        $calls_today = $result['count'];

        if ($calls_today >= $max_calls_per_day) {
            return [
                'allowed' => false,
                'message' => "Quota journalier atteint ($max_calls_per_day appels/jour). Réessayez demain.",
                'remaining' => 0
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Quota OK',
            'remaining' => $max_calls_per_day - $calls_today - 1
        ];

    } catch (Exception $e) {
        return [
            'allowed' => false,
            'message' => 'Erreur lors de la vérification du quota',
            'remaining' => 0
        ];
    }
}


function getStravaToken($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT access_token FROM oauth_tokens
            WHERE user_id = :user_id AND provider = 'strava'
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $user_id]);
        $token_data = $stmt->fetch();
        
        return $token_data ? $token_data['access_token'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function askGeminiCoachSwim($stats, $activitiesList, $experience, $swim_distance, $pool_size, $goals) {
    $apiKey = trim(GEMINI_API_KEY);
     $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=' . trim($apiKey);

    $prompt = "Tu es un coach de triathlon expérimenté, pédagogue et professionnel. ";
    $prompt .= "AVERTISSEMENTS IMPORTANTS:\n";
    $prompt .= "- Je ne suis pas responsable des blessures pouvant survenir lors de l'exécution de ce programme.\n";
    $prompt .= "- En cas de doute ou de douleur anormale, consulte un médecin ou un professionnel de santé.\n";
    $prompt .= "- Adapte toujours le programme à ta condition physique.\n\n";

    $prompt .= "PROFIL DE L'ATHLÈTE:\n";
    $prompt .= "- Niveau: " . ucfirst($experience) . "\n";
    $prompt .= "- Distance objectif: " . translateSwimDistance($swim_distance) . "\n";
    $prompt .= "- Bassin d'entraînement: " . $pool_size . "\n";
    $prompt .= "- Paramètres spécifiques: " . (!empty($goals) ? $goals : "Aucun spécifié") . "\n\n";

    $lastSwim = null;
    if (!empty($activitiesList)) {
        foreach ($activitiesList as $activity) {
            if (isset($activity['type']) && $activity['type'] === 'Swim') {
                $lastSwim = $activity;
                break;
            }
        }
    }

    if ($lastSwim) {
        $dist = round($lastSwim['distance'] / 1000, 2);
        $time = gmdate("H:i:s", $lastSwim['moving_time']);
        $prompt .= "- Dernière séance: " . $dist . "km en " . $time . "\n";
    }

    if (is_array($stats)) {
        $runKm = isset($stats['all_run_totals']['distance']) ? round($stats['all_run_totals']['distance'] / 1000, 0) : 0;
        $bikeKm = isset($stats['all_ride_totals']['distance']) ? round($stats['all_ride_totals']['distance'] / 1000, 0) : 0;
        $swimKm = isset($stats['all_swim_totals']['distance']) ? round($stats['all_swim_totals']['distance'] / 1000, 0) : 0;
        $prompt .= "- Totaux saison: Natation " . $swimKm . "km | Vélo " . $bikeKm . "km | Course " . $runKm . "km\n\n";
    }

    $prompt .= "CONSIGNES:\n";
    $prompt .= "1. Donne-moi une analyse très brève de ma forme actuelle (1 phrase max).\n";
    $prompt .= "2. Propose-moi UNE séance optimisée pour demain.\n";
    $prompt .= "3. Détaille: échauffement, corps de séance, retour au calme.\n";
    $prompt .= "4. Donne des fois et distances précises.";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => 4096
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        $erreur_google = json_decode($response, true);
        $message_precis = $erreur_google['error']['message'] ?? $response;
        
        return "Erreur Google (Code $http_code) : " . $message_precis;
    }
    
    $json = json_decode($response, true);

    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        $markdown_text = $json['candidates'][0]['content']['parts'][0]['text'];
        // Convertir le Markdown en HTML
        $html_text = Markdown::defaultTransform($markdown_text);
        return $html_text;
    } else {
        return "Erreur API: Réponse invalide. Response: " . $response;
    }
}


function translateSwimDistance($code) {
    $translations = [
        'short' => 'Sprint (100-300m)',
        'medium' => 'Distance moyenne (400-800m)',
        'long' => 'Longue distance (1.5km)',
        'ultra' => 'Ultra distance (1.5km+)'
    ];
    return $translations[$code] ?? $code;
}

?>