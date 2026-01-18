<?php
namespace Classes\Email\v1;

/**
 * Class Email
 * Represents an email with fields for sending and receiving.
 */
class Email implements \JsonSerializable {
    // Email properties
    private $from;
    private $to;
    private $cc;
    private $bcc;
    private $subject;
    private $body;
    private $htmlBody;
    private $attachments;
    private $headers;
    private $priority;
    private $charset;

    /**
     * Email constructor.
     * @param array $data Associative array with email fields
     */
    public function __construct(array $data = []) {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
        $this->from = $data['from'] ?? CCD77_NOREPLY_EMAIL;
        $this->to = $data['to'] ?? [];
        $this->cc = $data['cc'] ?? [];
        $this->bcc = $data['bcc'] ?? [];
        $this->subject = $data['subject'] ?? '';
        $this->body = $data['body'] ?? '';
        $this->htmlBody = $data['htmlBody'] ?? '';
        $this->attachments = $data['attachments'] ?? [];
        $this->headers = $data['headers'] ?? [];
        $this->priority = $data['priority'] ?? 'normal';
        $this->charset = $data['charset'] ?? 'UTF-8';
    }

    // Getters
    public function getFrom() { return $this->from; }
    public function getTo() { return $this->to; }
    public function getCc() { return $this->cc; }
    public function getBcc() { return $this->bcc; }
    public function getSubject() { return $this->subject; }
    public function getBody() { return $this->body; }
    public function getHtmlBody() { return $this->htmlBody; }
    public function getAttachments() { return $this->attachments; }
    public function getHeaders() { return $this->headers; }
    public function getPriority() { return $this->priority; }
    public function getCharset() { return $this->charset; }

    // Setters
    public function setFrom($from) { 
        if (!$this->validateEmail($from)) {
            throw new \InvalidArgumentException("Invalid 'from' email address: {$from}");
        }
        $this->from = $from; 
    }

    public function setTo($to) { 
        if (is_string($to)) {
            $to = [$to];
        }
        foreach ($to as $email) {
            if (!$this->validateEmail($email)) {
                throw new \InvalidArgumentException("Invalid 'to' email address: {$email}");
            }
        }
        $this->to = $to; 
    }

    public function addTo($email) {
        if (!$this->validateEmail($email)) {
            throw new \InvalidArgumentException("Invalid 'to' email address: {$email}");
        }
        $this->to[] = $email;
    }

    public function setCc($cc) { 
        if (is_string($cc)) {
            $cc = [$cc];
        }
        foreach ($cc as $email) {
            if (!$this->validateEmail($email)) {
                throw new \InvalidArgumentException("Invalid 'cc' email address: {$email}");
            }
        }
        $this->cc = $cc; 
    }

    public function addCc($email) {
        if (!$this->validateEmail($email)) {
            throw new \InvalidArgumentException("Invalid 'cc' email address: {$email}");
        }
        $this->cc[] = $email;
    }

    public function setBcc($bcc) { 
        if (is_string($bcc)) {
            $bcc = [$bcc];
        }
        foreach ($bcc as $email) {
            if (!$this->validateEmail($email)) {
                throw new \InvalidArgumentException("Invalid 'bcc' email address: {$email}");
            }
        }
        $this->bcc = $bcc; 
    }

    public function addBcc($email) {
        if (!$this->validateEmail($email)) {
            throw new \InvalidArgumentException("Invalid 'bcc' email address: {$email}");
        }
        $this->bcc[] = $email;
    }

    public function setSubject($subject) { $this->subject = $subject; }
    public function setBody($body) { $this->body = $body; }
    public function setHtmlBody($htmlBody) { $this->htmlBody = $htmlBody; }
    
    public function setAttachments($attachments) { $this->attachments = $attachments; }
    public function addAttachment($attachment) { $this->attachments[] = $attachment; }
    
    public function setHeaders($headers) { $this->headers = $headers; }
    public function addHeader($key, $value) { $this->headers[$key] = $value; }
    
    public function setPriority($priority) { 
        $validPriorities = ['low', 'normal', 'high'];
        if (!in_array($priority, $validPriorities)) {
            throw new \InvalidArgumentException("Invalid priority. Must be one of: " . implode(', ', $validPriorities));
        }
        $this->priority = $priority; 
    }
    
    public function setCharset($charset) { $this->charset = $charset; }

    /**
     * Validates email address format
     * @param string $email
     * @return bool
     */
    private function validateEmail($email) {
        // Extract email if it's in "Name <email@example.com>" format
        if (preg_match('/<(.+?)>/', $email, $matches)) {
            $email = $matches[1];
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Formats email with proper headers
     * @return string
     */
    public function format() {
        $output = [];
        
        // From
        if ($this->from) {
            $output[] = "From: {$this->from}";
        }
        
        // To
        if (!empty($this->to)) {
            $output[] = "To: " . implode(', ', $this->to);
        }
        
        // CC
        if (!empty($this->cc)) {
            $output[] = "Cc: " . implode(', ', $this->cc);
        }
        
        // BCC
        if (!empty($this->bcc)) {
            $output[] = "Bcc: " . implode(', ', $this->bcc);
        }
        
        // Subject
        if ($this->subject) {
            $output[] = "Subject: {$this->subject}";
        }
        
        // Additional headers
        foreach ($this->headers as $key => $value) {
            $output[] = "{$key}: {$value}";
        }
        
        // Priority
        if ($this->priority !== 'normal') {
            $priorityMap = ['low' => '5', 'high' => '1'];
            $output[] = "X-Priority: " . ($priorityMap[$this->priority] ?? '3');
        }
        
        // Charset
        $output[] = "Content-Type: text/plain; charset={$this->charset}";
        
        // Empty line before body
        $output[] = "";
        
        // Body
        $output[] = $this->body ?: $this->htmlBody;
        
        return implode("\r\n", $output);
    }

    /**
     * Formats email as HTML
     * @return string
     */
    public function formatAsHtml() {
        $body = $this->htmlBody ?: nl2br(htmlspecialchars($this->body));
        
        $html = "<!DOCTYPE html>\n";
        $html .= "<html>\n<head>\n";
        $html .= "<meta charset=\"{$this->charset}\">\n";
        $html .= "<title>{$this->subject}</title>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">\n";
        
        if ($this->subject) {
            $html .= "<h2>{$this->subject}</h2>\n";
        }
        
        $html .= "<div>{$body}</div>\n";
        $html .= "</div>\n</body>\n</html>";
        
        return $html;
    }

    /**
     * Serialize to JSON
     * @return array
     */
    public function jsonSerialize() {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'body' => $this->body,
            'htmlBody' => $this->htmlBody,
            'attachments' => $this->attachments,
            'headers' => $this->headers,
            'priority' => $this->priority,
            'charset' => $this->charset
        ];
    }

    /**
     * Convert to string representation
     * @return string
     */
    public function __toString() {
        return $this->format();
    }
}
