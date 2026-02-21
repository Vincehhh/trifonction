<?php

function callStravaAPI($url, $token) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Strava API Error: HTTP $httpCode for $url");
            return [];
        }
        
        $decoded = json_decode($response, true);
        return $decoded ?? [];
        
    } catch (Exception $e) {
        error_log("Strava API Exception: " . $e->getMessage());
        return [];
    }
}

function getStravaStats($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT access_token FROM oauth_tokens
            WHERE user_id = :user_id AND provider = 'strava'
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $user_id]);
        $token_data = $stmt->fetch();
        
        if (!$token_data) {
            return [];
        }
        
        $token = $token_data['access_token'];
        $stats = callStravaAPI('https://www.strava.com/api/v3/athlete/stats', $token);
        
        return $stats ?? [];
        
    } catch (Exception $e) {
        error_log("Get Strava Stats Error: " . $e->getMessage());
        return [];
    }
}


function getStravaActivities($pdo, $user_id, $limit = 20) {
    try {
        $stmt = $pdo->prepare("
            SELECT access_token FROM oauth_tokens
            WHERE user_id = :user_id AND provider = 'strava'
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $user_id]);
        $token_data = $stmt->fetch();
        
        if (!$token_data) {
            return [];
        }
        
        $token = $token_data['access_token'];
        $activities = callStravaAPI(
            "https://www.strava.com/api/v3/athlete/activities?per_page=$limit", 
            $token
        );
        
        return $activities ?? [];
        
    } catch (Exception $e) {
        error_log("Get Strava Activities Error: " . $e->getMessage());
        return [];
    }
}

?>
