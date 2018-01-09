<?php
/**
* @author Sachin kumar
*/
// Check error if exists
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class EncryDecryString {

    public function __construct()
    {
        $this->key = '124645';
    }


    public function encrypt($plain_string)
    {
    $iv = mcrypt_create_iv(
        mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC),
        MCRYPT_DEV_URANDOM
    );
    $encrypted = base64_encode(
        $iv .
        mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,
            hash('sha256', $this->key, true),
            $plain_string,
            MCRYPT_MODE_CBC,
            $iv
        )
    );
        return strtr($encrypted, '+/', '-_');
    }

    public function decrypt($encrypted_string)
    {
        $data = str_pad(strtr($encrypted_string, '-_', '+/'), strlen($encrypted_string) % 4, '=', STR_PAD_RIGHT);
        $data = base64_decode($data);
        $iv = substr($data, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));

        $decrypted = rtrim(
            mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            hash('sha256', $this->key, true),
            substr($data, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC)),
            MCRYPT_MODE_CBC,
            $iv
            ),
            "\0"
        );
        // return 
        return $decrypted;
    }
}

// Use the above library.
$new = new EncryDecryString();
echo $encrypted_string = $new->encrypt("sachin");
echo $new->decrypt($encrypted_string);