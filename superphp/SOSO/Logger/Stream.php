<?php
class SOSO_Logger_Stream extends SOSO_Logger_Abstract {
    protected $stream;
    protected $url;

    public function __construct($stream, $level = SOSO_Log::DEBUG, $bubble = true,$buffering=false){
        parent::__construct($level, $bubble,$buffering);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
    }

    public function close(){
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }
    
    protected function log(SOSO_Logger_Message $message){
        if (null === $this->stream) {
            if (!$this->url) {
                throw new LogicException('Missing stream url');
            }
            $this->stream = @fopen($this->url, 'a');
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new UnexpectedValueException(sprintf('The stream or file "%s" could not be opened; it may be invalid or not writable.', $this->url));
            }
        }
        fwrite($this->stream, (string) $message->getFormatted());
    }
}
