<?

class lEXC_ObExceptionHandler extends lEXC_DefaultExceptionHandler {
	/**
	 * Bufferul capturat pana la dezactivarea buffering-ului.
	 *
	 * Variabila foloseste pentru implementarea unui mecanism de salvare
	 * a bufferului capturat pana in momentul dezactivarii buffering-ului.
	 * Vezi descrierea metodei getBuffer() pentru mai multe detalii.
	 */
	var $buffer = null;

	/**
	 * Nivelul de buffering de la momentul activarii.
	 */
	var $initialObLevel;

	function onActivate() {
		$this->initialObLevel = ob_get_level();
		if (!$this->initialObLevel)
			ob_start();
	}

	function onDeactivate() {
		while (ob_get_level() > $this->initialObLevel)
			ob_end_flush();
	}

	/**
	 * @protected
	 */
	function &__disableBuffering() {
		$ret = "";
		while (ob_get_level()) {
			$ret = ob_get_contents() . $ret;
			ob_end_clean();
		}
		return $ret;
	}

	/**
	 * Dezactiveaza toate nivelurile de buffering, facand flush la
	 * bufferul capturat.
	 *
	 * Aceasta metoda trebuie apelata acolo unde este nevoie sa se
	 * trimita catre client un volum mare de date (de obicei preluate
	 * de pe alt site cu ajutorul extensiei curl) - vezi clasele din
	 * biblioteca lSiteMirror.
	 *
	 * Acolo unde este necesara trimiterea de output incremental catre
	 * client, ar trebui intai apelata aceasta metoda, dar apoi apelata
	 * explicit functia flush() pentru a trimite catre client continutul
	 * unui eventual buffer la nivelul serverului web.
	 */
	function disableBuffering() {
		while (ob_get_level())
			echo ob_get_clean();
		// aici smecheria este ca inca mai pot trimite headere daca nu
		// am avut output (spre deosebire de ob_end_flush(), care trimite
		// efectiv spre client, indiferent daca am avut sau nu output)
	}

	/**
	 * @protected
	 *
	 * Intoarce bufferul capturat, dezactivand output buffering-ul daca
	 * este necesar.
	 *
	 * Atunci cand trebuie adaugata functionalitate la metoda
	 * handleException() prin reimplementare si apelarea implementarii
	 * din clasa superioara, se poate folosi acest wrapper in jurul
	 * variabilei $this->buffer pentru a obtine acelasi rezultat
	 * indiferent de cate ori este apelata metoda.
	 */
	function &getBuffer() {
		if (null === $this->buffer)
			$this->buffer =& $this->__disableBuffering();
		return $this->buffer;
	}

	function handleException(&$code, &$desc, &$filename, &$line, &$context) {
		$this->getBuffer();
		parent::handleException($code, $desc, $filename, $line, $context);
	}
}

/**
 * Handler de exceptii care trimite mail cu detaliile exceptiei si
 * (optional) ascunde detaliile exceptiei catre utilizator afisand un
 * mesaj de eroare generic.
 */
class lEXC_MailerExceptionHandler extends lEXC_ObExceptionHandler {
	/**
	 * Intoarce adresa de mail la care se trimit detalii cu exceptia.
	 *
	 * In implementarea default intoarce null, care inseamna ca nu se
	 * trimite mail.
	 */
	function getMailTo() {
	}

	/**
	 * Intoarce numele utilizatorului autentificat.
	 */
	function getUsername() {
		return "N/A";
	}

	/**
	 * Intoarce o valoare bool care indica daca utilizatorului i se arata
	 * mesajul generic de exceptie.
	 */
	function getShowExceptionDetails() {
		return false;
	}

	/**
	 * Afiseaza mesajul generic de exceptie.
	 */
	function showExceptionMessage() {
	}

	function getMailBody(&$code, &$desc, &$filename, &$line, &$context) {
		$username = $this->getUsername();
		$buffer =& $this->getBuffer();
		$out = "An exception occured. Details follow:\n\n" .
			"File in service: " . $_SERVER["PHP_SELF"] . "\n" .
			"Logged user: " . $username . "\n" .
			"Error code: ". $this->errorCode2Str($code) . " (" . $code . ")\n" .
			"Error description: " . $desc . "\n" .
			"Filename: " . $filename . "\n" .
			"Line: " . $line . "\n" .
			"Backtrace:\n" . $this->renderBacktrace() .
			"Context:\n" . $this->varDump($context) . "\n";
		if (isset($GLOBALS['RMI_RemoteException'])) {
			$rmt =& $GLOBALS['RMI_RemoteException'];
			$out .= "\n------ REMOTE EXCEPTION details -------\n" .
				"Error code: " . $rmt["code"] . "\n" .
				"Error description: " . $rmt["description"] . "\n" .
				"Filename: " . $rmt["filename"] . "\n" .
				"Line: " . $rmt["line"] . "\n" .
				"Backtrace:\n" . $this->renderBacktrace($rmt["backtrace"]) .
				"Context:\n" . $this->varDump($rmt["context"]) .
				"\n";
		}
		$out .= "Captured output buffer:\n" . $buffer . "\n";
		return $out;
	}

	function getMailSubject(&$code, &$desc, &$filename, &$line, &$context) {
		$username = $this->getUsername();
		return "Exception in ".$_SERVER["PHP_SELF"]." by [" . $username . "]";
	}

	function handleException(&$code, &$desc, &$filename, &$line, &$context) {
		$this->removeGlobals($context);
		if (null !== ($mailTo = $this->getMailTo())) {
			mail($mailTo,
					$this->getMailSubject($code, $desc, $filename, $line, $context),
					$this->getMailBody($code, $desc, $filename, $line, $context)
				);
		}
		if ($this->getShowExceptionDetails())
			parent::handleException($code, $desc, $filename, $line, $context);
		else {
			$this->showExceptionMessage();
			exit;
		}
	}
}

?>
