<?php

namespace App;

use Supabase\Client as SupabaseClient;

class IServAuthenticator
{
    private SupabaseClient $supabase;
    private string $apiKey;

    public function __construct(SupabaseClient $supabase, string $apiKey)
    {
        $this->supabase = $supabase;
        $this->apiKey = $apiKey;
    }

    public function authenticateFromIServ(array $iservUser): array
    {
        $username = $iservUser['username'] ?? null;
        $iservId = $iservUser['id'] ?? null;
        $email = $iservUser['email'] ?? null;
        $role = $iservUser['role'] ?? 'teacher';
        $fullName = $iservUser['full_name'] ?? $username;

        if (!$username || !$iservId) {
            throw new \InvalidArgumentException('Missing IServ username or ID');
        }

        $userData = $this->syncUserWithSupabase($username, $iservId, $email, $role, $fullName);

        return [
            'success' => true,
            'user' => $userData,
            'token' => $this->generateSessionToken($userData['id']),
        ];
    }

    private function syncUserWithSupabase(
        string $username,
        int $iservId,
        ?string $email,
        string $role,
        string $fullName
    ): array {
        try {
            $existingUser = $this->supabase
                ->from('sportoase_users')
                ->select('*')
                ->eq('iserv_username', $username)
                ->maybeSingle()
                ->execute();

            if ($existingUser->data) {
                $this->supabase
                    ->from('sportoase_users')
                    ->update([
                        'iserv_id' => $iservId,
                        'email' => $email,
                        'role' => $role,
                        'full_name' => $fullName,
                        'is_active' => true,
                        'updated_at' => date('c'),
                    ])
                    ->eq('id', $existingUser->data['id'])
                    ->execute();

                return $existingUser->data;
            } else {
                $newUser = $this->supabase
                    ->from('sportoase_users')
                    ->insert([
                        'iserv_username' => $username,
                        'iserv_id' => $iservId,
                        'email' => $email,
                        'role' => $role,
                        'full_name' => $fullName,
                        'is_active' => true,
                        'created_at' => date('c'),
                    ])
                    ->execute();

                return $newUser->data[0] ?? null;
            }
        } catch (\Exception $e) {
            error_log("Error syncing user with Supabase: {$e->getMessage()}");
            throw $e;
        }
    }

    private function generateSessionToken(string $userId): string
    {
        return bin2hex(random_bytes(32));
    }

    public function validateRequest(string $token, array &$user): bool
    {
        $iservAuth = $_SERVER['HTTP_X_ISERV_AUTH'] ?? null;

        if (!$iservAuth) {
            return false;
        }

        try {
            $userInfo = $this->parseIServAuth($iservAuth);
            $user = $userInfo;
            return true;
        } catch (\Exception $e) {
            error_log("Invalid IServ auth: {$e->getMessage()}");
            return false;
        }
    }

    private function parseIServAuth(string $auth): array
    {
        $parts = explode('|', $auth);
        if (count($parts) < 3) {
            throw new \InvalidArgumentException('Invalid IServ auth format');
        }

        return [
            'id' => (int) $parts[0],
            'username' => $parts[1],
            'role' => $parts[2] ?? 'teacher',
        ];
    }
}
