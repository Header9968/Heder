<?php
/**
 * Registration controller with WhatsApp confirmation
 */

Class USER
{
    private array $userColumns = [];

    public function main( array $GET = [] )
    {
        $lang = $this->DevTools->lang;
        $otpAvailable = $this->isOtpAvailable();
        $error = '';

        $form = [
            'name' => trim( $_POST['name'] ?? '' ),
            'phone' => trim( $_POST['phone'] ?? '' ),
            'school' => trim( $_POST['school'] ?? '' )
        ];

        if( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['register_submit'] ) )
        {
            try
            {
                $result = $this->handleRegistration( $form, $otpAvailable );

                if( $result['redirect'] )
                {
                    header( "Location: {$result['redirect']}" );
                    exit;
                }

                return $this->DevTools->ThemeMsg(
                    $lang['register_success_title'],
                    sprintf( $lang['register_success_login_hint'], $result['login_url'] ),
                    false
                );
            }
            catch( Exception $e )
            {
                $error = $e->getMessage();
            }
        }

        $Tpl = $this->DevTools->ThemeLoad( 'register' );

        $this->DevTools->ThemeSetElement( '{register.error}', $error ? '<div class="billing-register-error">' . $error . '</div>' : '' );
        $this->DevTools->ThemeSetElement( '{register.value.name}', $this->escape( $form['name'] ) );
        $this->DevTools->ThemeSetElement( '{register.value.phone}', $this->escape( $form['phone'] ) );
        $this->DevTools->ThemeSetElement( '{register.value.school}', $this->escape( $form['school'] ) );

        $this->DevTools->ThemeSetElement( '{register.action}', '/' . $this->DevTools->config['page'] . '.html/register/' );
        $this->DevTools->ThemeSetElement( '{register.code.url}', '/' . $this->DevTools->config['page'] . '.html/register/code/' );
        $this->DevTools->ThemeSetElement( '{register.cooldown}', $this->getCooldown() );
        $this->DevTools->ThemeSetElement( '{register.requires_code}', $otpAvailable ? '1' : '0' );
        $this->DevTools->ThemeSetElement( '{register.code.disabled}', $otpAvailable ? '' : $lang['register_code_disabled'] );
        $this->DevTools->ThemeSetElement( '{register.login.url}', $this->getLoginUrl() );

        $this->DevTools->ThemeSetElement( '{register.label.title}', $lang['register_title'] );
        $this->DevTools->ThemeSetElement( '{register.label.name}', $lang['register_name'] );
        $this->DevTools->ThemeSetElement( '{register.label.phone}', $lang['register_phone'] );
        $this->DevTools->ThemeSetElement( '{register.label.school}', $lang['register_school'] );
        $this->DevTools->ThemeSetElement( '{register.label.password}', $lang['register_password'] );
        $this->DevTools->ThemeSetElement( '{register.label.code}', $lang['register_code'] );
        $this->DevTools->ThemeSetElement( '{register.button.send_code}', $lang['register_send_code'] );
        $this->DevTools->ThemeSetElement( '{register.label.button}', $lang['register_button'] );
        $this->DevTools->ThemeSetElement( '{register.label.have_account}', $lang['register_have_account'] );
        $this->DevTools->ThemeSetElement( '{register.label.login_here}', $lang['register_login_here'] );
        $this->DevTools->ThemeSetElement( '{register.policy}', $lang['register_policy'] );

        $this->DevTools->ThemeSetElement( '{register.msg.phone_invalid}', $lang['register_phone_invalid'] );
        $this->DevTools->ThemeSetElement( '{register.msg.code_sent}', $lang['register_code_sent'] );
        $this->DevTools->ThemeSetElement( '{register.msg.code_disabled}', $lang['register_code_disabled'] );
        $this->DevTools->ThemeSetElement( '{register.msg.status_wait}', $lang['register_status_wait'] );
        $this->DevTools->ThemeSetElement( '{register.msg.status_default}', $lang['register_status_default'] );
        $this->DevTools->ThemeSetElement( '{register.msg.error_general}', $lang['register_error_general'] );

        return $this->DevTools->Show( $Tpl, false );
    }

    public function code( array $GET = [] )
    {
        header( 'Content-Type: application/json; charset=' . $this->DevTools->dle['charset'] );

        try
        {
            if( $_SERVER['REQUEST_METHOD'] !== 'POST' )
            {
                throw new Exception('Method not allowed');
            }

            $payload = json_decode( file_get_contents('php://input'), true );
            $payload = is_array( $payload ) ? $payload : $_POST;

            $hash = trim( $payload['user_hash'] ?? $payload['hash'] ?? $_POST['user_hash'] );
            $this->DevTools->CheckHash( $hash );

            if( ! $this->isOtpAvailable() )
            {
                echo json_encode([
                    'status' => 'ok',
                    'requires_code' => false,
                    'message' => $this->DevTools->lang['register_code_disabled']
                ], JSON_UNESCAPED_UNICODE );
                exit;
            }

            $phone = $this->normalizePhone( $payload['phone'] ?? '' );

            if( ! $phone )
            {
                throw new Exception( $this->DevTools->lang['register_phone_invalid'] );
            }

            $result = $this->sendOtp( $phone );

            echo json_encode( $result, JSON_UNESCAPED_UNICODE );
        }
        catch( Exception $e )
        {
            http_response_code( 400 );
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE );
        }

        exit;
    }

    private function handleRegistration( array &$form, bool $otpRequired ): array
    {
        $lang = $this->DevTools->lang;

        $this->DevTools->CheckHash( $_POST['user_hash'] );

        $name = trim( strip_tags( $form['name'] ) );
        $school = trim( strip_tags( $form['school'] ) );
        $phone = $this->normalizePhone( $form['phone'] ?? '' );
        $password = (string) ($_POST['password'] ?? '');
        $code = trim( $_POST['code'] ?? '' );

        if( $phone )
        {
            $form['phone'] = $phone;
        }

        if( mb_strlen( $name, $this->DevTools->dle['charset'] ) < 2 )
        {
            throw new Exception( $lang['register_name_required'] );
        }

        if( mb_strlen( $school, $this->DevTools->dle['charset'] ) < 2 )
        {
            throw new Exception( $lang['register_school_required'] );
        }

        if( ! $phone )
        {
            throw new Exception( $lang['register_phone_invalid'] );
        }

        if( strlen( $password ) < 8 )
        {
            throw new Exception( $lang['register_password_rule'] );
        }

        if( $otpRequired )
        {
            if( ! $code )
            {
                throw new Exception( $lang['register_code_required'] );
            }

            $this->validateOtp( $phone, $code );
        }

        $this->assertPhoneAvailable( $phone );

        $userId = $this->createUserRecord( $name, $phone, $password, $school );
        $this->saveProfile( $userId, $phone, $school, $name );

        if( $otpRequired )
        {
            $this->markOtpVerified( $phone );
        }

        $redirect = trim( $this->DevTools->config['register_success_redirect'] );

        return [
            'redirect' => $redirect,
            'login_url' => $this->getLoginUrl()
        ];
    }

    private function assertPhoneAvailable( string $phone ): void
    {
        $db = $this->DevTools->LQuery->db;
        $safePhone = $db->safesql( $phone );

        $profile = $db->super_query( "SELECT profile_id FROM " . USERPREFIX . "_billing_user_profiles WHERE phone='{$safePhone}' LIMIT 1" );

        if( ! empty( $profile['profile_id'] ) )
        {
            throw new Exception( $this->DevTools->lang['register_error_exists'] );
        }
    }

    private function sendOtp( string $phone ): array
    {
        $record = $this->getOtpRecord( $phone );
        $this->assertCanSend( $record );

        $code = (string) random_int( 100000, 999999 );

        $client = new WhatsappClient([
            'token' => $this->DevTools->config['whatsapp_token'],
            'phone_id' => $this->DevTools->config['whatsapp_phone_id'],
            'template' => $this->DevTools->config['whatsapp_template'],
            'language' => $this->DevTools->config['whatsapp_language'] ?: 'ru',
            'stub_mode' => $this->DevTools->config['whatsapp_stub_mode'],
            'debug_phone' => $this->DevTools->config['whatsapp_debug_phone']
        ], function( $level, $message, $context = [] )
        {
            $this->DevTools->Logger( 'whatsapp', $level, $message, $context );
        });

        $result = $client->sendCode( $phone, $code );

        if( ! $result['success'] )
        {
            throw new Exception( $result['message'] ?: $this->DevTools->lang['register_error_general'] );
        }

        $this->persistOtp( $phone, $code, $record );

        return [
            'status' => 'ok',
            'requires_code' => true,
            'cooldown' => $this->getCooldown(),
            'message' => $this->DevTools->lang['register_code_sent']
        ];
    }

    private function getOtpRecord( string $phone ): ?array
    {
        $db = $this->DevTools->LQuery->db;
        $safePhone = $db->safesql( $phone );

        $row = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_whatsapp_codes WHERE phone='{$safePhone}' LIMIT 1" );

        return $row ?: null;
    }

    private function assertCanSend( ?array $record ): void
    {
        if( ! $record )
        {
            return;
        }

        $lang = $this->DevTools->lang;
        $now = $this->DevTools->_TIME;

        if( $record['blocked_until'] > $now )
        {
            throw new Exception( $lang['register_code_blocked'] );
        }

        $cooldown = $this->getCooldown();

        if( $record['last_sent'] && ( $record['last_sent'] + $cooldown ) > $now )
        {
            $wait = ( $record['last_sent'] + $cooldown ) - $now;
            throw new Exception( sprintf( $lang['register_rate_limited'], $wait ) );
        }

        $dailyLimit = max( 1, intval( $this->DevTools->config['whatsapp_daily_limit'] ) );
        $dayStart = $this->getDayStart( $now );
        $dailySent = ( $record['daily_reset'] >= $dayStart ) ? intval( $record['daily_sent'] ) : 0;

        if( $dailySent >= $dailyLimit )
        {
            throw new Exception( $lang['register_code_blocked'] );
        }
    }

    private function persistOtp( string $phone, string $code, ?array $record ): void
    {
        $db = $this->DevTools->LQuery->db;
        $safePhone = $db->safesql( $phone );
        $hash = $db->safesql( password_hash( $code, PASSWORD_BCRYPT ) );
        $now = $this->DevTools->_TIME;
        $ttlMinutes = max( 1, intval( $this->DevTools->config['whatsapp_code_ttl'] ) );
        $expires = $now + ( $ttlMinutes * 60 );
        $dayStart = $this->getDayStart( $now );
        $sessionToken = $db->safesql( $this->getSessionToken() );
        $ip = $db->safesql( $this->getUserIp() );

        if( $record )
        {
            $dailySent = ( $record['daily_reset'] >= $dayStart ) ? intval( $record['daily_sent'] ) + 1 : 1;

            $db->query("
                UPDATE " . USERPREFIX . "_billing_whatsapp_codes
                SET code_hash='{$hash}',
                    attempts='0',
                    resend_count = resend_count + 1,
                    daily_sent='{$dailySent}',
                    daily_reset='{$dayStart}',
                    last_sent='{$now}',
                    expires_at='{$expires}',
                    blocked_until='0',
                    verified='0',
                    verified_at='0',
                    session_token='{$sessionToken}',
                    ip='{$ip}',
                    payload='',
                    updated_at='{$now}'
                WHERE phone='{$safePhone}'
            ");
        }
        else
        {
            $db->query("
                INSERT INTO " . USERPREFIX . "_billing_whatsapp_codes
                (phone, code_hash, attempts, resend_count, daily_sent, daily_reset, last_sent, expires_at, blocked_until, verified, verified_at, session_token, ip, channel, payload, created_at, updated_at)
                VALUES
                ('{$safePhone}', '{$hash}', '0', '1', '1', '{$dayStart}', '{$now}', '{$expires}', '0', '0', '0', '{$sessionToken}', '{$ip}', 'whatsapp', '', '{$now}', '{$now}')
            ");
        }
    }

    private function validateOtp( string $phone, string $code ): void
    {
        $lang = $this->DevTools->lang;
        $record = $this->getOtpRecord( $phone );

        if( ! $record )
        {
            throw new Exception( $lang['register_code_invalid'] );
        }

        $now = $this->DevTools->_TIME;

        if( $record['blocked_until'] > $now )
        {
            throw new Exception( $lang['register_code_blocked'] );
        }

        if( $record['expires_at'] < $now )
        {
            throw new Exception( $lang['register_code_expired'] );
        }

        if( ! password_verify( $code, $record['code_hash'] ) )
        {
            $this->incrementOtpAttempts( $record );
            throw new Exception( $lang['register_code_invalid'] );
        }
    }

    private function incrementOtpAttempts( array $record ): void
    {
        $limit = max( 1, intval( $this->DevTools->config['whatsapp_attempt_limit'] ) );
        $attempts = intval( $record['attempts'] ) + 1;
        $blockedUntil = 0;

        if( $attempts >= $limit )
        {
            $attempts = 0;
            $blockedUntil = $this->DevTools->_TIME + $this->getBlockSeconds();
        }

        $db = $this->DevTools->LQuery->db;
        $safePhone = $db->safesql( $record['phone'] );

        $db->query("
            UPDATE " . USERPREFIX . "_billing_whatsapp_codes
            SET attempts='{$attempts}',
                blocked_until='{$blockedUntil}',
                updated_at='{$this->DevTools->_TIME}'
            WHERE phone='{$safePhone}'
        ");
    }

    private function markOtpVerified( string $phone ): void
    {
        $db = $this->DevTools->LQuery->db;
        $safePhone = $db->safesql( $phone );

        $db->query("
            UPDATE " . USERPREFIX . "_billing_whatsapp_codes
            SET verified='1',
                verified_at='{$this->DevTools->_TIME}',
                attempts='0'
            WHERE phone='{$safePhone}'
        ");
    }

    private function createUserRecord( string $name, string $phone, string $password, string $school ): int
    {
        $db = $this->DevTools->LQuery->db;
        $login = $this->buildLogin( $name, $phone );
        $passwordHash = md5( md5( $password ) );
        $group = intval( $this->DevTools->config['register_group'] ) ?: 4;
        $ip = $this->getUserIp();
        $xfields = $this->buildXFields( $phone, $school );

        $fields = [
            'name' => $login,
            'password' => $passwordHash,
            'email' => $this->buildEmailFromPhone( $phone ),
            'reg_date' => date( "Y-m-d H:i:s", $this->DevTools->_TIME ),
            'lastdate' => $this->DevTools->_TIME,
            'logged_ip' => $ip,
            'last_ip' => $ip,
            'user_group' => $group,
            'banned' => 0,
            'allow_mail' => 0,
            'hash' => md5( $this->DevTools->_TIME . $login . microtime(true) ),
            'fullname' => $name,
            'info' => '',
            'signature' => '',
            'xfields' => $xfields,
            'news_num' => 0,
            'comm_num' => 0,
            'pm_all' => 0,
            'pm_unread' => 0
        ];

        $columns = $this->getUserColumns();
        $set = [];

        foreach( $fields as $column => $value )
        {
            if( isset( $columns[ $column ] ) )
            {
                $set[] = "`{$column}`='" . $db->safesql( $value ) . "'";
            }
        }

        if( ! count( $set ) )
        {
            throw new Exception( $this->DevTools->lang['register_error_general'] );
        }

        $db->query( "INSERT INTO " . USERPREFIX . "_users SET " . implode( ',', $set ) );
        $userId = $db->insert_id();

        if( ! $userId )
        {
            throw new Exception( $this->DevTools->lang['register_error_general'] );
        }

        $this->DevTools->Logger( 'register', 'info', 'New user registered', ['user_id' => $userId, 'phone' => $phone] );

        return $userId;
    }

    private function buildLogin( string $name, string $phone ): string
    {
        $mode = $this->DevTools->config['register_login_field'] === 'name' ? 'name' : 'phone';

        if( $mode === 'name' )
        {
            $candidate = $this->slugLogin( $name );
            if( ! $candidate )
            {
                $candidate = 'user' . substr( preg_replace('/\D/', '', $phone ), -4 );
            }
        }
        else
        {
            $candidate = preg_replace('/[^0-9]/', '', $phone );
        }

        return $this->ensureUniqueLogin( $candidate );
    }

    private function slugLogin( string $value ): string
    {
        $value = mb_strtolower( $value, $this->DevTools->dle['charset'] );
        $value = preg_replace('/[^a-z0-9_]/u', '', $value);

        return trim( $value );
    }

    private function ensureUniqueLogin( string $login ): string
    {
        $db = $this->DevTools->LQuery->db;
        $safeLogin = $db->safesql( $login );
        $exists = $db->super_query( "SELECT user_id FROM " . USERPREFIX . "_users WHERE name='{$safeLogin}' LIMIT 1" );

        if( empty( $exists['user_id'] ) )
        {
            return $login;
        }

        return $this->ensureUniqueLogin( $login . random_int( 10, 99 ) );
    }

    private function getUserColumns(): array
    {
        if( $this->userColumns )
        {
            return $this->userColumns;
        }

        $db = $this->DevTools->LQuery->db;
        $db->query( "SHOW COLUMNS FROM " . USERPREFIX . "_users" );

        while( $row = $db->get_row() )
        {
            $this->userColumns[ $row['Field'] ] = true;
        }

        return $this->userColumns;
    }

    private function saveProfile( int $userId, string $phone, string $school, string $displayName ): void
    {
        $db = $this->DevTools->LQuery->db;

        $db->query("
            INSERT INTO " . USERPREFIX . "_billing_user_profiles
            (user_id, phone, school, display_name, meta, created_at, updated_at)
            VALUES
            (
                '" . intval( $userId ) . "',
                '" . $db->safesql( $phone ) . "',
                '" . $db->safesql( $school ) . "',
                '" . $db->safesql( $displayName ) . "',
                '" . $db->safesql( json_encode(['source' => 'billing']) ) . "',
                '{$this->DevTools->_TIME}',
                '{$this->DevTools->_TIME}'
            )
        ");
    }

    private function getLoginUrl(): string
    {
        return $this->DevTools->config['register_login_url'] ?: '/index.php?do=login';
    }

    private function getCooldown(): int
    {
        $value = intval( $this->DevTools->config['whatsapp_resend_timeout'] );

        return $value > 0 ? $value : 60;
    }

    private function getBlockSeconds(): int
    {
        $minutes = intval( $this->DevTools->config['whatsapp_block_minutes'] );
        $minutes = $minutes > 0 ? $minutes : 30;

        return $minutes * 60;
    }

    private function getDayStart( int $time ): int
    {
        return mktime( 0, 0, 0, date('n', $time), date('j', $time), date('Y', $time) );
    }

    private function isOtpAvailable(): bool
    {
        return $this->DevTools->config['whatsapp_enabled']
            && $this->DevTools->config['whatsapp_token']
            && $this->DevTools->config['whatsapp_phone_id']
            && $this->DevTools->config['whatsapp_template'];
    }

    private function normalizePhone( ?string $phone ): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '' );

        if( ! $digits )
        {
            return '';
        }

        if( $digits[0] === '8' )
        {
            $digits = '7' . substr( $digits, 1 );
        }

        if( $digits[0] !== '7' )
        {
            $digits = '7' . ltrim( $digits, '0' );
        }

        $digits = substr( $digits, 0, 11 );

        if( strlen( $digits ) !== 11 )
        {
            return '';
        }

        return '+' . $digits;
    }

    private function buildEmailFromPhone( string $phone ): string
    {
        $host = parse_url( $this->DevTools->dle['http_home_url'], PHP_URL_HOST ) ?: 'example.com';
        $clean = preg_replace('/[^0-9]/', '', $phone );

        return $clean . '@' . $host;
    }

    private function buildXFields( string $phone, string $school ): string
    {
        $fields = [
            'phone' => $phone,
            'school' => $school
        ];

        $result = [];

        foreach( $fields as $key => $value )
        {
            $result[] = $key . '|' . $value;
        }

        return implode( '||', $result );
    }

    private function escape( string $value ): string
    {
        return htmlspecialchars( $value, ENT_QUOTES, $this->DevTools->dle['charset'] );
    }

    private function getUserIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?: '0.0.0.0';
    }

    private function getSessionToken(): string
    {
        if( session_status() === PHP_SESSION_NONE )
        {
            session_start();
        }

        return session_id() ?: md5( microtime(true) );
    }
}
