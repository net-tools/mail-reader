<?php 

namespace Nettools\MailReader\Tests;


use \Nettools\MailReader\Engine;




class EngineTest extends \PHPUnit\Framework\TestCase
{
    public function testPlainHtml()
	{
		// we test multipart/alternative : text/plain, text/html
		// we test headers with simple values and headers with multiple values (separated by ';')
		// we test headers with folding, with or without quotes (")
		// we test iso-8859-1 charset converted to utf8
        $mail = Engine::fromString(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.plainhtml.eml'));
		$this->assertNotNull($mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Multipart::class, $mail->email);
		$this->assertEquals('alternative', $mail->email->getType());
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\TextPlainContent::class, $mail->email->getPart(0));
		$this->assertStringStartsWith( 
							"At your request, please find below the information you requested :\n" . 
                            "- xxxx\n" . 
                            "Test with french accents : éà\n",

                            $mail->email->getPart(0)->getText()
						);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\TextHtmlContent::class, $mail->email->getPart(1));
		$this->assertStringStartsWith( 
							"<html><head><title></title><head><body>\n" .
							"At your request, please find below the information you requested :<br/>",
            
                            $mail->email->getPart(1)->getHtml()
						);



		// multipart/related (embedding)
		// content-disposition is not present, we guess it thanks to the content-id attribute
        $mail = Engine::fromString(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.inline.eml'));
		$this->assertNotNull($mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Multipart::class, $mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Multipart::class, $mail->email->getPart(0));		
		$this->assertEquals('alternative', $mail->email->getPart(0)->getType());
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\TextPlainContent::class, $mail->email->getPart(0)->getPart(0));		
		$this->assertStringStartsWith("this is a *unit test* with inline attachment", $mail->email->getPart(0)->getPart(0)->getText());
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Embedding::class, $mail->email->getPart(1));
		$fname = $mail->email->getPart(1)->getData();
		$this->assertFileEquals(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.inline.png', $fname);
		Engine::clean($mail->email);
		$this->assertFileDoesNotExist($fname);	// destroy a supprimé le fichier temporaire
		


		// multipart/mixed
		// attachment with text/plain content and CRLF newlines
		$mail = Engine::fromString(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_attachment.eml'));
		$this->assertNotNull($mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Multipart::class, $mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\TextPlainContent::class, $mail->email->getPart(0));
        $this->assertEquals('mixed', $mail->email->getType());
		$this->assertStringStartsWith(
							"Hi,\n" .
							"\n" .
							"\n" .
							"This is a unit test",
            
                            $mail->email->getPart(0)->getText()
						);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Attachment::class, $mail->email->getPart(1));
		$fname = $mail->email->getPart(1)->getData();
        // gzdecode because GIT or FTP software may convert CRLF to LF
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_attachment.bin.gz')), file_get_contents($fname)); 
		Engine::clean($mail->email);
		$this->assertFileDoesNotExist($fname);	

		
		
		// multipart/mixed
		// attachment with text/plain content and LF newlines
		$mail = Engine::fromString(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.LF_attachment.eml'));
		$this->assertNotNull($mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Multipart::class, $mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\TextPlainContent::class, $mail->email->getPart(0));
        $this->assertEquals('mixed', $mail->email->getType());
		$this->assertStringStartsWith(
							"Hi,\n" .
							"\n" .
							"\n" .
							"This is a unit test",
            
                            $mail->email->getPart(0)->getText()
						);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Attachment::class, $mail->email->getPart(1));
		$fname = $mail->email->getPart(1)->getData();
        // gzdecode because GIT or FTP software may convert CRLF to LF
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.LF_attachment.bin.gz')), file_get_contents($fname)); 
		Engine::clean($mail->email);
		$this->assertFileDoesNotExist($fname);	
		

		
		// multipart/mixed with 2 attachments (no multipart/alternative html part)
		$mail = Engine::fromString(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_LF_attachments.eml'));
		$this->assertNotNull($mail);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Multipart::class, $mail->email);
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\TextPlainContent::class, $mail->email->getPart(0));
        $this->assertEquals('mixed', $mail->email->getType());
        $this->assertStringStartsWith('two attachments', $mail->email->getPart(0)->getText());
		$this->assertEquals(3, $mail->email->getCount()); // 3 parts in the mailmultipart : the text/plain one, and the 2 attachments
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Attachment::class, $mail->email->getPart(1));
		$this->assertInstanceOf(\Nettools\Mailing\MailBuilder\Attachment::class, $mail->email->getPart(2));

        $fname = $mail->email->getPart(1)->getData();
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.CRLF_attachment.bin.gz')), file_get_contents($fname)); 
		$fname2 = $mail->email->getPart(2)->getData();
		$this->assertEquals(gzdecode(file_get_contents(__DIR__ . '/data/' . substr(strrchr(__CLASS__, '\\'),1) . '.LF_attachment.bin.gz')), file_get_contents($fname2)); 
		Engine::clean($mail->email);
		$this->assertFileDoesNotExist($fname);	
		$this->assertFileDoesNotExist($fname2);
	}

}


?>