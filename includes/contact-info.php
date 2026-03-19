<?php
// Contact Information Configuration
// Acest fișier conține informațiile de contact centralizate

class ContactInfo {
    const JOURNALIST_NAME = 'David Nyikora';
    const EMAIL = 'contact@matchday.ro';
    const PHONE = '0740 173 581';
    const PHONE_FORMATTED = '+40740173581';
    const WEBSITE = 'https://matchday.ro';
    const ROLE = 'Jurnalist sportiv';
    const SPECIALTY = 'Fotbal românesc și internațional';
    
    public static function getAuthorName() {
        return self::JOURNALIST_NAME;
    }
    
    public static function getEmail() {
        return self::EMAIL;
    }
    
    public static function getPhone() {
        return self::PHONE;
    }
    
    public static function getFormattedPhone() {
        return self::PHONE_FORMATTED;
    }
    
    public static function getContactInfo() {
        return [
            'name' => self::JOURNALIST_NAME,
            'email' => self::EMAIL,
            'phone' => self::PHONE,
            'phone_formatted' => self::PHONE_FORMATTED,
            'website' => self::WEBSITE,
            'role' => self::ROLE,
            'specialty' => self::SPECIALTY
        ];
    }
    
    public static function getVCard() {
        return "BEGIN:VCARD
VERSION:3.0
FN:" . self::JOURNALIST_NAME . "
ORG:MatchDay.ro
TITLE:" . self::ROLE . "
EMAIL:" . self::EMAIL . "
TEL:" . self::PHONE_FORMATTED . "
URL:" . self::WEBSITE . "
END:VCARD";
    }
}
?>
