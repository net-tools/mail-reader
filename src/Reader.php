<?php
/**
 * Reader
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\MailReader;


use \Nettools\Mailing\MailBuilder\Content;
use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Class to parse an email content and get a Content object along with top-level headers
 */
class Reader
{
	public $email = null;
	public $headers = null;
	
	
	
	/**
	 * Constructor
	 *
	 * @param MailBuilder\Content $mail
	 * @param string[] $headers
	 */
	function __construct(Content $email, Headers $headers)
	{
		$this->email = $email;
		$this->headers = $headers;
	}
	
	
	
	/**
	 * Clear temp files used for embeddings and attachments
	 */
	function clean()
	{
		Engine::clean($this->email);
	}
	
	
	
	/**
	* Decode email from a string
	* 
	* @param string $data Email string to decode
	* @return Reader
	* @throws Error
	*/
	static function fromString($data)
	{
		$o = Engine::fromString($data);
		return new Reader($o->email, Engine::decodeHeaders($o->headers));
	}
	
	
	
	/**
	* Decode email from a file
	* 
	* @param string $file Path to email to read
	* @return Reader
	* @throws Error
	*/
	static function fromFile($file)
	{
		if ( file_exists($file) )
			return self::fromString(file_get_contents($file));
		else
			throw new Error("File '$file' not found.");
	}
}

?>