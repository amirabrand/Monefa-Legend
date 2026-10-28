<?php
/**
 * Bale Bot API helper — Monefa Legend (T25)
 * Docs: https://docs.bale.ai  |  Base: https://tapi.bale.ai/bot{token}/{method}
 * Config lives OUTSIDE webroot: CONFSDATA . 'baleConfig.php'
 */
if (!defined('BaleApi')) {

    class BaleApi
    {
        public static function cfgFile()
        {
            return CONFSDATA . 'baleConfig.php';
        }

        /** @return array $baleBotToken, $baleBotUsername, $baleProviderToken, $baleAmountMultiplier, $baleWebhookSecret */
        public static function config()
        {
            $baleBotToken = '';
            $baleBotUsername = '';
            $baleProviderToken = '';
            $baleAmountMultiplier = 10; // game price (Toman) -> Rial
            $baleWebhookSecret = '';
            $f = self::cfgFile();
            if (is_file($f)) {
                include $f;
            }
            return array(
                'token' => (string)$baleBotToken,
                'username' => (string)$baleBotUsername,
                'provider' => (string)$baleProviderToken,
                'multiplier' => (int)$baleAmountMultiplier > 0 ? (int)$baleAmountMultiplier : 10,
                'secret' => (string)$baleWebhookSecret,
            );
        }

        public static function saveConfig(array $cfg)
        {
            $f = self::cfgFile();
            $token = trim($cfg['token']);
            $user = ltrim(trim($cfg['username']), '@');
            $provider = trim($cfg['provider']);
            $mult = max(1, (int)$cfg['multiplier']);
            $secret = trim($cfg['secret']);
            if ($secret === '') {
                $secret = bin2hex(random_bytes(16));
            }
            $php = "<?php\n"
                 . "// Bale bot config — managed via admin panel (?p=balebot) — T25\n"
                 . "// DO NOT commit real tokens to git.\n"
                 . "\$baleBotToken        = " . var_export($token, true) . ";\n"
                 . "\$baleBotUsername     = " . var_export($user, true) . ";\n"
                 . "\$baleProviderToken   = " . var_export($provider, true) . ";\n"
                 . "\$baleAmountMultiplier = " . $mult . ";\n"
                 . "\$baleWebhookSecret   = " . var_export($secret, true) . ";\n";
            file_put_contents($f, $php, LOCK_EX);
            @chmod($f, 0660);
            return $secret;
        }

        public static function call($method, array $params = array(), $timeout = 10)
        {
            $cfg = self::config();
            if ($cfg['token'] === '') {
                return array('ok' => false, 'description' => 'bale-bot-token-missing');
            }
            $ch = curl_init('https://tapi.bale.ai/bot' . rawurlencode($cfg['token']) . '/' . $method);
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
            ));
            $res = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            $j = json_decode((string)$res, true);
            if (!is_array($j)) {
                return array('ok' => false, 'description' => 'bale-no-response' . ($err ? (': ' . $err) : ''));
            }
            return $j;
        }

        public static function botLink()
        {
            $cfg = self::config();
            return $cfg['username'] !== '' ? ('https://ble.ir/' . $cfg['username']) : '';
        }
    }
}
