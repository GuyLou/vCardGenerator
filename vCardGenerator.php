<?php
/**
 * This class is for generating a string in microformat (vCard or hCard) with supplied data.
 * It currently only supports vCard format version 3.0.
 *
 * Available parameters (description in parentheses) and suggested format (default value in parentheses):
 *    - 'strict' (true = enforce strict vCard format, false = try your best) => false
 *    - 'acceptedPhoneNumberTypes' => 'work', 'home', 'mobile'
 *    - 'acceptedAddressTypes' => 'work', 'home'
 *
 * @author Daniel Imhoff
 */

class vCardGenerator {
   private $params = array(
      'strict' => false,
      'acceptedPhoneNumberTypes' => array('work', 'home', 'mobile'),
      'acceptedAddressTypes' => array('work', 'home'),
   );

   private $fullName;   // setFullName() is provided for convenience, but if any of the below are set,
   private $firstName;  // they will all be used to construct a full name. It is best to use
   private $middleName; // setFullName() by itself, or use the setFirstName(), setMiddleName(), and
   private $lastName;   // setLastName() functions by themselves.

   private $organization;
   private $title;

   private $phoneNumbers = array();
   private $addresses = array();

   private $emailAddress;

   private $lastRevision;

   /**
    * The constructor can accept parameters for the class.
    *
    * @param array $params An associated array of parameters for use in the generator.
    */
   public function __construct(array $params = null) {
      if(isset($params)) {
         $this->setParams($params);
      }
   }

   /**
    * When attempted to convert this object to a string, this function will generate and return the vCard.
    */
   public function __toString() {
      try {
         return $this->generateVCard();
      }
      catch(Exception $e) {
         return $e->getMessage();
      }
   }

   /**
    * This function will add/overwrite the parameters of the class.
    *
    * @param array $params An associated array of parameters for use in the generator.
    */
   public function setParams(array $params) {
      $this->params += $params;
   }

   /**
    * This function will add/overwrite a parameter of the class.
    *
    * @param string $key The key of the parameter.
    * @param mixed $value The value of the parameter.
    */
   public function setParam($key, $value) {
      $this->params[$key] = $value;
   }

   /**
    * This function will set the first name of the person this vCard represents.
    *
    * @param string $name The first name.
    */
   public function setFirstName($name) {
      $this->firstName = $name;
   }

   /**
    * This function will set the middle name of the person this vCard represents.
    *
    * @param string $name The middle name.
    */
   public function setMiddleName($name) {
      $this->middleName = $name;
   }

   /**
    * This function will set the last name of the person this vCard represents.
    *
    * @param string $name The last name.
    */
   public function setLastName($name) {
      $this->lastName = $name;
   }

   /**
    * This function will set the full name of the person this vCard represents.
    *
    * @param string $name The full name. ex: Daniel Imhoff, Daniel W Imhoff
    */
   public function setFullName($name) {
      $this->fullName = $name;
   }

   /**
    * This function will set the organization of the person this vCard represents.
    *
    * @param string $organization The organization's name. ex: UW-Platteville
    */
   public function setOrganization($organization) {
      $this->organization = $organization;
   }

   /**
    * This function will set the title of the person this vCard represents.
    *
    * @param string $title The title.
    */
   public function setTitle($title) {
      $this->title = $title;
   }

   /**
    * This function will add a phone number to the vCard.
    *
    * @param string $type The type of phone number. ex: work, home, mobile (default: home if null is passed)
    * @param mixed $phoneNumber The phone number itself.
    */
   public function addPhoneNumber($type, $phoneNumber = null) {
      if(!isset($type)) {
         $type = 'home';
      }

      if($this->params['strict'] && !in_array($type, $this->params['acceptedPhoneNumberTypes'])) {
         throw new Exception('Phone number type not allowed.');
      }

      $this->phoneNumbers[] = array(
         'type' => strtoupper($type),
         'number' => $this->formatPhoneNumber($phoneNumber),
      );
   }

   /**
    * This function will add a street address to the vCard.
    *
    * @param string $type The type of address. ex: work, home (default: home if null is passed)
    * @param string $address The street address itself. Lines terminated by \n.
    */
   public function addAddress($type, $address) {
      if(!isset($type)) {
         $type = 'home';
      }

      if($this->params['strict'] && !in_array($type, $this->params['acceptedAddressTypes'])) {
         throw new Exception('Address type not allowed.');
      }

      $this->addresses[] = array(
         'type' => strtoupper($type),
         'address' => $address, // No formatting done for street address.. x.x
      );
   }

   /**
    * This function will set the email address of the vCard.
    *
    * @param string $emailAddress The email address.
    */
   public function setEmailAddress($emailAddress) {
      if($this->params['strict'] && !$this->isValidEmailAddress($emailAddress)) {
         throw new Exception('Email address is not valid.');
      }

      $this->emailAddress= $emailAddress;
   }

   /**
    * Set the last revision to this vCard. If not set, the generator will default the last revision to
    * the current second.
    */
   public function setLastRevision($time) {
      $this->lastRevision = $time;
   }

   /**
    * This builds the vCard and returns it in string format.
    *
    * @return string The formatted vCard.
    */
   public function generateVCard() {
      $output = 'BEGIN:VCARD' . "\r\n"
              . 'VERSION:3.0' . "\r\n"
              . 'N:' . $this->formatName() . "\r\n"
              . 'FN:' . $this->fullName . "\r\n"
              . 'ORG:' . $this->organization . "\r\n"
              . 'TITLE:' . $this->title . "\r\n";

      foreach($this->phoneNumbers as $phoneNumber) {
         $output .= 'TEL;TYPE=' . $phoneNumber['type'] . ',VOICE:' . $phoneNumber['number'] . "\r\n";
      }

      foreach($this->addresses as $address) {
         $output .= 'ADR;TYPE=' . $address['type'] . ':;;' . $this->formatAddress($address['address']) . "\r\n"
                  . 'LABEL;TYPE=' . $address['type'] . ':' . rtrim(str_replace("\n", '\n', $address['address']), '\n') . "\r\n";
      }

      $output .= 'EMAIL;TYPE=PREF,INTERNET:' . $this->emailAddress . "\r\n"
               . 'REV:' . $this->formatTime(isset($this->lastRevision) ? $this->lastRevision : time()) . "\r\n"
               . 'END:VCARD' . "\r\n";

      return $output;
   }

   /**
    * This function is necessary when first, middle and last names are not specifically set. It attempts to determine
    * them from the given full name.
    */
   private function parseFullName() {
      $ary = explode(' ', $this->fullName);

      $this->firstName = array_shift($ary);

      switch(sizeof($ary)) {
         case 1:
            $this->lastName = array_shift($ary);
            break;
         case 2:
            $this->middleName = array_shift($ary);
            $this->lastName = array_shift($ary);
            break;
         default:
            $this->middleName = array_shift($ary);

            foreach($ary as $name) {
               $this->lastName .= $name . ' ';
            }

            rtrim($this->lastName);
            break;
      }
   }

   /**
    * This function formats a name for the N: field in vCard.
    *
    * @return The formatted name.
    */
   private function formatName() {
      if(!isset($this->firstName) && !isset($this->middleName) && !isset($this->lastName)) {
         $this->parseFullName();
      }
      else {
         $this->fullName = $this->firstName . ' ' . $this->middleName . ' ' . $this->lastName;
      }

      return $this->lastName . ';' . $this->firstName . (isset($this->middleName) ? ';' . $this->middleName : '');
   }

   /**
    * This function formats a phone number into valid vCard format. Phone numbers can be given in any format, and the
    * function will try it's best to return a phone number in valid vCard format depending on the strict parameter.
    *
    * @param mixed $phoneNumber The phone number to format.
    * @return string The formatted phone number.
    */
   private function formatPhoneNumber($phoneNumber) {
      $phoneNumber = preg_replace('/[^0-9]/', '', (string) $phoneNumber);

      switch(strlen($phoneNumber)) {
         case 7:
            return preg_replace('/([0-9]{3})([0-9]{4})/', '$1-$2', $phoneNumber);

         case 10:
            return preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '($1) $2-$3', $phoneNumber);

         case 11:
            return preg_replace('/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/', '$1 ($2) $3-$4', $phoneNumber);

         default:
            if($this->params['strict']) {
               throw new Exception('Phone number is of an invalid length. Phone numbers must be 7, 10, or 11 characters long.');
            }

            return $phoneNumber;
      }
   }

   /**
    * This function formats an address into valid vCard format for ADR:
    *
    * @return string The address.
    */
   private function formatAddress($address) {
      $lines = preg_split('/\\\n|\\n/', trim($address));

      $secondLine = explode(' ', str_replace(',', ' ', $lines[1]));

      $zip = array_pop($secondLine);
      $state = array_pop($secondLine);
      $city = '';

      foreach($secondLine as $cityPart) {
         $city .= $cityPart . ' ';
      }

      $lines[1] = rtrim($city) . ';' . $state . ';' . $zip;

      return implode(';', $lines);
   }

   /**
    * This function will format a time and date into the correct vCard format for revision time.
    *
    * @param string $time The date and time, in any format strtotime() can recognize.
    * @return string The formatted time and date.
    */
   private function formatTime($time) {
      if((string) (int) $time != $time && false === $time = strtotime($time)) {
         throw new Exception('strtotime() could not convert your time to a valid timestamp.');
      }

      return date('Y-m-d\TH:i:s\Z', $time);
   }

   /**
    * This function will validate an email address.
    *
    * @param string $emailAddress The email address to validate.
    * @return true if the email address is valid, false otherwise.
    */
   private function isValidEmailAddress($emailAddress) {
      return false !== filter_var(trim($emailAddress), FILTER_VALIDATE_EMAIL);
   }
}