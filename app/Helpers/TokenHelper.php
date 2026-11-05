<?php
namespace App\Helpers;

class TokenHelper {

    public function GetUserPermissions($tokenData) {
        // Decode the token data JSON string to an array
        $tokenArray = json_decode($tokenData, true);

        // Check for 'permissions' in the token data
        if (isset($tokenArray['permissions'])) {
            return $tokenArray['permissions'];
        }

        // Return an empty array if 'permissions' is not present
        return [];
    }

    public function GetClientID($tokenData) {
        // Decode the token data JSON string to an array
        $tokenArray = json_decode($tokenData, true);

        // Check for 'permissions' in the token data
        if (isset($tokenArray['azp'])) {
            return $tokenArray['azp'];
        }

        // Return an empty array if 'permissions' is not present
        return null;
    }
}
