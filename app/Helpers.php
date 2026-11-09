<?php

// use Brick\PhoneNumber\PhoneNumber;
use App\Models\Core\InappNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (!function_exists('generateOtp')) {
    function generateOtp(int $length, int $duration, int $user_id): array
    {
        $otp = '';
        $generator = '0123456789987654321085321479632014796698';
        for ($i = 1; $i <= $length; $i++) {
            $otp .= substr($generator, (rand() % (strlen($generator))), 1);
        }

        $expires_at = date('Y-m-d H:i:s', strtotime('now +' . $duration . 'minutes'));

        // Delete any existing token
        DB::table('otps')->where([
            ['user_id', $user_id],
        ])->delete();

        // create new code
        DB::table('otps')->insert([
            'user_id' => $user_id,
            'otp' => $otp,
            'expires_at' => $expires_at,
        ]);

        return ['otp' => $otp, 'expires_at' => $expires_at];
    }
}

if (!function_exists('getActorName')) {
    function getActorName(Request $request): string
    {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return 'System';
        }

        return $user->full_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'System';
    }
}

function sendNotification(int $user_id, string $type, string|array $data, ?string $url = null)
{
    InappNotification::create([
        'user_id' => $user_id,
        'type' => $type,
        'data' => is_array($data) ? json_encode($data) : $data,
        'url' => $url,
        'slug' => uniqid('kwiq') . $user_id * rand(1, 99999),
    ]);
}

if (!function_exists('formatDate')) {
    function formatDate($dateString, $format = 'Y-m-d')
    {
        try {
            $date = new DateTime($dateString);

            return $date->format($format);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('validatePhoneNumber')) {
    function validatePhoneNumber($phone_number)
    {
        // dd($phone_number);
        // return PhoneNumber::parse($phone_number, '234')->isValidNumber() ? true : false;

        if (in_array(strtolower(getenv('APP_ENV')), ['production', 'online'])) {
            return PhoneNumber::parse($phone_number, '234')->isValidNumber() ? true : false;
        }
    }
}
