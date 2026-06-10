<?php

namespace App\Services;

use App\Models\WordPress\User;

class AdminPasswordValidator
{
    public function validate(User $user, string $password): bool
    {
        if ($user->admin_password) {
            try {
                if (\Illuminate\Support\Facades\Hash::check($password, $user->admin_password)) {
                    return true;
                }
            } catch (\Exception) {
                // fall through
            }
        }

        if ($user->user_pass) {
            return $this->checkWordPressPassword($password, $user->user_pass);
        }

        return false;
    }

    private function checkWordPressPassword(string $password, string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        if (str_starts_with($hash, '$wp$')) {
            return password_verify($password, substr($hash, 4));
        }

        if (strlen($hash) === 34 && (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$'))) {
            return $this->checkPhpassPassword($password, $hash);
        }

        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$')) {
            return password_verify($password, $hash);
        }

        return $this->checkPhpassPassword($password, $hash);
    }

    private function checkPhpassPassword(string $password, string $hash): bool
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $output = '*0';
        if (str_starts_with($hash, $output)) {
            $output = '*1';
        }

        $id = substr($hash, 0, 3);
        if ($id !== '$P$' && $id !== '$H$') {
            return false;
        }

        $count_log2 = strpos($itoa64, $hash[3]);
        if ($count_log2 < 7 || $count_log2 > 30) {
            return false;
        }

        $count = 1 << $count_log2;
        $salt = substr($hash, 4, 8);
        if (strlen($salt) !== 8) {
            return false;
        }

        $hash_result = md5($salt . $password, true);
        do {
            $hash_result = md5($hash_result . $password, true);
        } while (--$count);

        $output = substr($hash, 0, 12);
        $output .= $this->encode64($hash_result, 16, $itoa64);

        return $output === $hash;
    }

    private function encode64(string $input, int $count, string $itoa64): string
    {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
