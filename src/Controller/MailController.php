<?php

namespace Src\Controller;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailController
{

  private $requestMethod;
  private $mail;

  public function __construct($requestMethod)
  {
    $this->requestMethod = $requestMethod;
    $this->mail = new PHPMailer(true);
  }

  public function processRequest(): void
  {

    $response = match ($this->requestMethod) {
      'POST' => $this->sendMail(),
      default => $this->notFoundResponse(),
    };

    header($response['status_code_header']);
    if ($response['body']) {
      echo $response['body'];
    }
  }


  private function sendMail(): array
  {
    $input = (array)json_decode(file_get_contents('php://input'), TRUE);
    if (!$this->validateMail($input)) {
      return $this->unprocessableEntityResponse();
    }
    $res = $this->sendMessage($input);

    if ($res != "ok") {
      return $this->mailServiceErrResponse($res);
    }

    $response['status_code_header'] = 'HTTP/1.1 200 Ok';
    $response['body'] = json_encode([
      'message' => 'Message has been sent',
    ]);
    return $response;
  }

  private function sendMessage($input): string
  {
    try {
      $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;            //Enable verbose debug output
      $this->mail->isSMTP();                                  //Send using SMTP
      $this->mail->Host = $input["host"];                     //Set the SMTP server to send through
      $this->mail->SMTPAuth = true;                           //Enable SMTP authentication
      $this->mail->Username = $input["username"];             //SMTP username
      $this->mail->Password = $input["password"];             //SMTP password

      $encryption = PHPMailer::ENCRYPTION_SMTPS;

      if ($input["encryption"] == "starttls") {
        $encryption = PHPMailer::ENCRYPTION_STARTTLS;
      }

      $this->mail->SMTPSecure = $encryption;                  //Enable implicit TLS encryption
      $this->mail->Port = $input["port"];                     //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

      $this->mail->setFrom($input["username"], $input["sender_name"]);
      $this->mail->addAddress($input["to"]);                  //Add a recipient

      $this->mail->isHTML(true);                       //Set email format to HTML
      $this->mail->Subject = $input["subject"];
      $this->mail->Body = $input["message"];
//      $this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
      $this->mail->send();
      return "ok";
    } catch (Exception $e) {
      return "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
    }
  }

  private function validateMail($input): bool
  {
    if (!isset($input['host'])) {
      return false;
    }
    if (!isset($input['port'])) {
      return false;
    }
    if (!isset($input['username'])) {
      return false;
    }
    if (!isset($input['password'])) {
      return false;
    }
    if (!isset($input['sender_name'])) {
      return false;
    }
    if (!isset($input['to'])) {
      return false;
    }
    if (!isset($input['subject'])) {
      return false;
    }
    if (!isset($input['message'])) {
      return false;
    }
    return true;
  }

  private function unprocessableEntityResponse(): array
  {
    $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
    $response['body'] = json_encode([
      'error' => 'Invalid input'
    ]);
    return $response;
  }

  private function mailServiceErrResponse($message): array
  {
    $response['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
    $response['body'] = json_encode([
      'error' => $message
    ]);
    return $response;
  }

  private function notFoundResponse(): array
  {
    $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
    $response['body'] = null;
    return $response;
  }
}