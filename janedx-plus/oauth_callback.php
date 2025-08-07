<?php
require_once 'config.php';


if (!isset($_GET['code']) || !isset($_GET['state'])) {
    redirect('login.php?error=oauth_failed');
}

$code = $_GET['code'];
$state = $_GET['state'];
$provider = '';

// Determine provider from state
if (strpos($state, 'google_') === 0) {
    $provider = 'google';
} elseif (strpos($state, 'facebook_') === 0) {
    $provider = 'facebook';
} else {
    redirect('login.php?error=invalid_state');
}

try {
    if ($provider === 'google') {
        $user_data = handleGoogleCallback($code);
    } elseif ($provider === 'facebook') {
        $user_data = handleFacebookCallback($code);
    }
    
    if ($user_data) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE oauth_provider = ? AND oauth_id = ?");
        $stmt->execute([$provider, $user_data['id']]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            // Login existing user
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['user_name'] = $existing_user['name'];
            redirect('dashboard.php');
        } else {
            // Check if email already exists with different provider
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$user_data['email']]);
            if ($stmt->fetch()) {
                redirect('login.php?error=email_exists');
            }
            
            // Create new user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, oauth_provider, oauth_id, profile_picture) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_data['name'], $user_data['email'], $provider, $user_data['id'], $user_data['picture']])) {
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $user_data['name'];
                redirect('dashboard.php?welcome=1');
            } else {
                redirect('login.php?error=registration_failed');
            }
        }
    } else {
        redirect('login.php?error=oauth_failed');
    }
} catch (Exception $e) {
    error_log("OAuth Error: " . $e->getMessage());
    redirect('login.php?error=oauth_failed');
}

function handleGoogleCallback($code) {
    $token_url = 'https://oauth2.googleapis.com/token';
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    // Exchange code for access token
    $post_data = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => BASE_URL . '/oauth_callback.php'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    
    if (!isset($token_data['access_token'])) {
        return false;
    }
    
    // Get user info
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url . '?access_token=' . $token_data['access_token']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $user_response = curl_exec($ch);
    curl_close($ch);
    
    $user_info = json_decode($user_response, true);
    
    if (!$user_info || !isset($user_info['id'])) {
        return false;
    }
    
    return [
        'id' => $user_info['id'],
        'name' => $user_info['name'],
        'email' => $user_info['email'],
        'picture' => $user_info['picture'] ?? null
    ];
}

function handleFacebookCallback($code) {
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $user_info_url = 'https://graph.facebook.com/me';
    
    // Exchange code for access token
    $token_params = http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'code' => $code,
        'redirect_uri' => BASE_URL . '/oauth_callback.php'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url . '?' . $token_params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    
    if (!isset($token_data['access_token'])) {
        return false;
    }
    
    // Get user info
    $user_params = http_build_query([
        'fields' => 'id,name,email,picture',
        'access_token' => $token_data['access_token']
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url . '?' . $user_params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $user_response = curl_exec($ch);
    curl_close($ch);
    
    $user_info = json_decode($user_response, true);
    
    if (!$user_info || !isset($user_info['id'])) {
        return false;
    }
    
    return [
        'id' => $user_info['id'],
        'name' => $user_info['name'],
        'email' => $user_info['email'] ?? null,
        'picture' => $user_info['picture']['data']['url'] ?? null
    ];
}
?>