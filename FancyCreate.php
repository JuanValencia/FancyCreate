<?php
/**
 * MediaWiki Fancy Create v 0.1
 * Register the <fancycreate> tag
 * Created by Juan Valencia
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 * (I copied this comment from somewhere... don't recall where)
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "FancyUpload is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

/**
 * Register hooks and credits
 */

$wgExtensionCredits['parserhook'][] = array(
        'name' => 'Fancy Create',
        'author' => 'Juan Valencia',
        'description' => 'Allows easy article creation from wherever there is a <nowiki><fancycreate></nowiki> tag.',
        'url' => 'http://www.mediawiki.org/wiki/Extension:FancyCreate',
        'version' => '0.2'
);

$wgExtensionFunctions[] = "wfFancyCreate"; //register the hook that gets MW to call this extension
$wgAjaxExportList[] = 'efFancyCreateIsTitleOk'; //register ajax function

function wfFancyCreate() {
	global $wgParser;
	$wgParser->setHook( "fancycreate", "RenderFancyCreate" ); //set the <fancyupload> tag to point to the following function
}

function RenderFancyCreate( $input, $args, $parser, $frame ) {
	$parser->disableCache();
	global $wgOut;
	global $wgScriptPath;
	$wgOut->addScript("<style type='text/css'>@import '$wgScriptPath/extensions/FancyCreate/FancyCreate.css';</style>");
	$wgOut->addScript(<<<EOJS

<script>	

	String.prototype.ucfirst = function() {
		return this.charAt(0).toUpperCase() + this.substr(1);
	}

	function writeError(mError) {
		var errorDiv = document.getElementById("FancyCreateError");
		errorDiv.innerHTML = mError;
	}


	function doFancyCreateSubmit() {

		finalTitle = getFinalTitle();
		if (finalTitle) {
			sajax_do_call( 'efFancyCreateIsTitleOk', [finalTitle, 'html'], function(request) 
				{ 
					var trimmedResponse = request.responseText.replace(/^\s+/, '');
					if (trimmedResponse == "Yes") {
						var fancyCreateUrl = wgServer + wgScript + "?title=" + finalTitle + "&action=edit"
						window.location = fancyCreateUrl;
					} else if (trimmedResponse == "No") {
						if (confirm ("This article title already exists, would you like go and edit that page?")) {
							var fancyCreateUrl = wgServer + wgScript + "?title=" + finalTitle + "&action=edit";
							window.location = fancyCreateUrl;
						}
					} else {
						alert("I don't understand");
					}
				}
			);
		}
		
	}

	function doFancyCreateCheck() {	
		var title = getFinalTitle();
		if (title) {
			sajax_do_call('efFancyCreateIsTitleOk', [title, 'html'], function(request) 
				{ 
					var trimmedResponse = request.responseText.replace(/^\s+/, '');
					if (trimmedResponse == "Yes") {
						writeError(title + " is available.");
					} else if (trimmedResponse == "No") {
						writeError(title + " is currently used.");
					} else {
						alert("I don't understand");
					}
				}
			);
		}
	}

	function getFinalTitle() {
		var fancyCreateTitle = document.getElementById("FancyCreateArticleTitle").value;
		var fancyCreateNSList = document.getElementById('FancyCreateNSList');
		var fancyCreateNS = fancyCreateNSList.options[fancyCreateNSList.selectedIndex].innerHTML;

		var finalTitle = "";
		if (!fancyCreateTitle) {
			writeError("Please enter a title!");
			return 0;
		} else if (fancyCreateTitle.replace(/^\s+/, '') == '') {
			writeError("Please enter a title with more than just spaces!");
			return 0;
		} else if (!fancyCreateNS) {
			finalTitle = fancyCreateTitle.replace(/^\s+/, '').ucfirst();
		} else if (fancyCreateNS.replace(/^\s+/, '') == '') {
			finalTitle = fancyCreateTitle.replace(/^\s+/, '').ucfirst();
		} else {
			finalTitle = fancyCreateNS.replace(/^\s+/, '') + ":" + fancyCreateTitle.replace(/^\s+/, '').ucfirst();
		}
		finalTitle = finalTitle.replace(/\s+$/, '');
		return finalTitle;
	}
</script>
EOJS
	);

	$mNSOutput = "<select class='FancyCreateNSList' id='FancyCreateNSList'>";
	$namespaces = SearchEngine::searchableNamespaces();
	foreach ($namespaces as $option) {
		$mNSOutput .= "<option class='FancyCreateNSOption' name='FancyCreateNSOption'>$option</option>";
	}
        $mNSOutput .= "</select>";

	$mOutput = <<<EOM
<div class="FancyCreateDiv">
	<form class="FancyCreateForm" onsubmit="return false;">
		<h4 class="FancyCreateHeader">Create a Wiki Article</h4>
		<p id='FancyCreateExplanation' class='FancyCreateExplanation'>(This tool creates new pages in this wiki.  It can also check if a page name is already in use.  Page names within a Namespace must be unique. Therefore, we recommend names that include the project, dates or other features to make them unique.)</p>
		<label class="FancyCreateLabel">
			<span class=FancyCreateLabelText>Article Title:</span>
			<input id="FancyCreateArticleTitle" class="FancyCreateArticleTitle" type="text"></input><br />
		</label><br />
		<label class="FancyCreateLabel">
			<span class="FancyCreateLabelText">Article Namespace:</span>
			$mNSOutput
			<div class='FancyCreateClear' style='clear:both;'>
			</div><a id="FancyCreateCheck" class="FancyCreateCheck" onclick="doFancyCreateCheck()">Check Name</a><br />
		</label><br />
		<label class="FancyCreateLabel">
			<a id="FancyCreateSubmit" class="FancyCreateSubmit" onclick="doFancyCreateSubmit()">Create Article</a>
		</label>
		<div class="FancyCreateError" id="FancyCreateError"></div><br />
		<div class='FancyCreateClear' style='clear:both;'></div>
	</form>
</div>
EOM;
	$mOutput = str_replace("\n","",$mOutput);
	return $mOutput;
}

function efFancyCreateIsTitleOk($finalTitle) {

	$nt = Title::newFromText( $finalTitle);
 	# If the article title already exists, then output a warning. And give people the option of continuing or going back.
	if ($nt->exists()) {
		return "No";
	} else {
		return "Yes";
	}

}




?>
