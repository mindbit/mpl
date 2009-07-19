<?
require_once('AbstractErrorHandler.php');

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
			echo(lSTR_AsciiTableRow::renderCells(
						array(
							"Error code",
							lEXC_ErrorCode2STR($code)." (".$code.")"
							),
						array(EXC_LeftWidth, EXC_RightWidth)));
			echo(lSTR_AsciiTableRow::renderRowSeparator(
						array(EXC_LeftWidth, EXC_RightWidth)));
			echo(lSTR_AsciiTableRow::renderCells(
						array("Error description", $desc),
						array(EXC_LeftWidth, EXC_RightWidth)));
			echo(lSTR_AsciiTableRow::renderRowSeparator(
						array(EXC_LeftWidth, EXC_RightWidth)));
			if($filename) {
				echo(lSTR_AsciiTableRow::renderCells(
							array("File", $filename),
							array(EXC_LeftWidth, EXC_RightWidth)));
				echo(lSTR_AsciiTableRow::renderRowSeparator(
							array(EXC_LeftWidth, EXC_RightWidth)));
			}
			if ($line) {
				echo(lSTR_AsciiTableRow::renderCells(
							array("Line", $line),
							array(EXC_LeftWidth, EXC_RightWidth)));
				echo(lSTR_AsciiTableRow::renderRowSeparator(
							array(EXC_LeftWidth, EXC_RightWidth)));
			}
			echo(lSTR_AsciiTableRow::renderCells(
						array("Backtrace", $backtrace),
						array(EXC_LeftWidth, EXC_RightWidth)));
			echo(lSTR_AsciiTableRow::renderRowSeparator(
						array(EXC_LeftWidth, EXC_RightWidth)));
			echo(lSTR_AsciiTableRow::renderCells(
						array("Context", lSTR_GetOutput('var_dump', $context)),
						array(EXC_LeftWidth, EXC_RightWidth)));
			echo(lSTR_AsciiTableRow::renderRowSeparator(
						array(EXC_LeftWidth, EXC_RightWidth)));
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
			echo(lSTR_AsciiTableRow::renderRowSeparator(array(EXC_TableWidth + 3)));
			echo(lSTR_AsciiTableRow::renderCells(
						array('EXCEPTION'), array(EXC_TableWidth + 3),
						array(1), array(STR_ALIGN_CENTER)));
			echo(lSTR_AsciiTableRow::renderRowSeparator(
						array(EXC_LeftWidth, EXC_RightWidth)));
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
		return parent::backtraceFilter($stackLevel);
	}
}

?>
