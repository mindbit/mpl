<?
require_once('AbstractErrorHandler.php');
require_once('AsciiTable.php');

define("EXC_TABLE_WIDTH",    72);
define("EXC_LEFT_WIDTH",     25);
define("EXC_RIGHT_WIDTH",    EXC_TABLE_WIDTH - EXC_LEFT_WIDTH);

define("STR_ALIGN_LEFT",		STR_PAD_RIGHT);
define("STR_ALIGN_CENTER",		STR_PAD_BOTH);
define("STR_ALIGN_RIGHT",		STR_PAD_LEFT);

class GenericErrorHandler extends AbstractErrorHandler {
	function renderExceptionElements(&$code, &$desc, &$filename,
			&$line, &$context, &$backtrace) {
		if(isset($_SERVER['SERVER_NAME'])) {
		?>
<tr>
 <td bgcolor="white"><b>Error code</b></td>
 <td bgcolor="white"><?= $this->errorCodeToStr($code)." (".$code.")"?></td>
</tr>
<tr>
 <td bgcolor="white"><b>Error description</b></td>
 <td bgcolor="white"><?=$desc;?></td>
</tr>
<?
			if($filename) {
			?>
<tr>
 <td bgcolor="white"><b>File</b></td>
 <td bgcolor="white"><?=$filename;?></td>
</tr>
<?
			}
			if ($line) {
			?>
<tr>
 <td bgcolor="white"><b>Line</b></td>
 <td bgcolor="white"><?=$line;?></td>
</tr>
<?
			}
			?>
<tr>
 <td bgcolor="white"><b>Backtrace</b></td>
 <td bgcolor="white"><?=$backtrace;?></td>
</tr>
<tr>
 <td bgcolor="white"><b>Context</b></td>
 <td bgcolor="white"><pre><?= htmlentities($this->varDump($context));?></pre></td>
</tr>
		<?
		} else {
			echo(AsciiTable::renderCells(
						array(
							"Error code",
							$this->errorCodeToStr($code)." (".$code.")"
							),
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			echo(AsciiTable::renderRowSeparator(
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			echo(AsciiTable::renderCells(
						array("Error description", $desc),
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			echo(AsciiTable::renderRowSeparator(
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			if($filename) {
				echo(AsciiTable::renderCells(
							array("File", $filename),
							array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
				echo(AsciiTable::renderRowSeparator(
							array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			}
			if ($line) {
				echo(AsciiTable::renderCells(
							array("Line", $line),
							array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
				echo(AsciiTable::renderRowSeparator(
							array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			}
			echo(AsciiTable::renderCells(
						array("Backtrace", $backtrace),
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			echo(AsciiTable::renderRowSeparator(
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			echo(AsciiTable::renderCells(
						array("Context", "x"),// lSTR_GetOutput('var_dump', $context)),
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
			echo(AsciiTable::renderRowSeparator(
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
		}
	}

	function __handleError($code, $desc, $filename, $line, &$context, $backtrace = null) {
		if(isset($_SERVER['SERVER_NAME'])) {
			$bt = nl2br($this->renderBacktrace($backtrace));
		?>
<center>
<table border=1 cellspacing=1>
<tr>
<th bgcolor="black" colspan=2><font color="white">Exception</th></th>
</tr>
<?
		} else {
			$bt = $this->renderBacktrace($backtrace);
			echo(AsciiTable::renderRowSeparator(array(EXC_TABLE_WIDTH + 3)));
			echo(AsciiTable::renderCells(
						array('EXCEPTION'), array(EXC_TABLE_WIDTH + 3),
						array(1), array(STR_ALIGN_CENTER)));
			echo(AsciiTable::renderRowSeparator(
						array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH)));
		}
		$this->removeGlobals($context);
		$this->renderExceptionElements($code, $desc, $filename,
				$line, $context, $bt);
		/* FIXME: pentru RMI ar trebui sa schimb exception handlerul in loc
		   sa tratez aici afisarea exceptiei remote */
		if(isset($GLOBALS['RMI_RemoteException'])) {
			echo('<tr><th bgcolor="black" colspan=2><font color="white">'.
					'Remote exception details</th></tr>');
			$bt=nl2br($this->renderBacktrace(
						$GLOBALS['RMI_RemoteException']['backtrace']));
			$this->RenderExceptionElements(
					$GLOBALS['RMI_RemoteException']['code'],
					$GLOBALS['RMI_RemoteException']['description'],
					$GLOBALS['RMI_RemoteException']['filename'],
					$GLOBALS['RMI_RemoteException']['line'],
					$GLOBALS['RMI_RemoteException']['context'],
					$bt);
		}
		if(isset($_SERVER['SERVER_NAME'])) {
		?>
</table>
</center>
<?
		}
		exit;
	}

	function backtraceFilter(&$frame) {
		if (isset($frame["class"]) && $frame["class"] == "ErrorHandler" && $frame["function"] == "handleError")
			return true;
		return parent::backtraceFilter($frame);
	}
}

?>
