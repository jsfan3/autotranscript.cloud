<?php
session_start();
if(!isset($_SESSION['token'])) $_SESSION['token'] = getToken(50);
set_time_limit(0);
$SUBFILENAME = "video.srt";
$SHOW_TRANSCRIPT_UTILITY = "/var/www/transcript.sh";
$GENERATE_SUBTITLES_UTILITY = "/var/www/autosubtitles.sh";

include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';
$securimage = new Securimage();


?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="noindex, nofollow" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="MobileOptimized" content="width" />
    <meta name="HandheldFriendly" content="true" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="cleartype" content="on" />
    <title>Auto-transcript & Auto-subtitles for MP4 videos</title> 
    <style type="text/css">
        body { font-size: 1.3em; color: black; font-family: sans-serif; background-color: #FFFFCC;}
        h1 { font-size: 1.5em; font-weight: bold; }
        h2 { font-size: 1.4em; font-weight: bold; }
        description { font-size: 0.9em;}
        note { font-size: 0.8em; font-style: italic;}
        pre { color: black; font-family: sans-serif; white-space: pre-wrap;}
        error, alert { color: red; }
        green { color: #28ae6d; }
        url { color: blue; }
        author { font-size: 0.8em; font-style: italic;}
        strong { font-weight: bold; }
        italic { font-style: italic; }
        form { border: 1px; display: box; box-pack: center; box-align: center; border-color:red; background-color: #fff1f0; border-style: dotted; padding:5px;}
        #disqus_thread { width:80%; margin: 0 auto; border: 1px; border-color:red; background-color: #fff1f0; border-style: dotted; padding:10px;}

        #wait {
          display:none;
          color:red;
          font-style: italic;
        }

        input[type=submit] {
          display:none;
        }

        input[type=radio]:checked ~ input[type=submit] {
          display:block;
        }
    </style>
    <script>
    function showWait() {
       document.getElementById('wait').style.display = "block";
    }
    </script>
</head>
<body>
<h1>Auto-transcript & Auto-subtitles for MP4 videos</h1>
<h3><a href="http://www.autotranscript.cloud/">www.autotranscript.cloud</a></h3>
<author><a href="<?php htmlspecialchars($_SERVER['PHP_SELF']); ?>">Reload the page</a> - Coding by <a target='transcript' href="https://www.informatica-libera.net/">Francesco Galgani</a></author><br /><br />

<?php

/*** START OF PHP FUNCTIONS ***/

function sendMail($url) {
    $to      = 'francesco@galgani.it';
    $subject = 'Auto-transcript è stato utilizzato';
    $message = 'È stato richiesto il video: <br /><a href="'.$url.'">'.$url.'</a>';

    mail_utf8($to, $to, $to, $subject, $message);
}

function mail_utf8($to, $from_user, $from_email, $subject = '(No subject)', $message = '')
   {
      $from_user = "=?UTF-8?B?".base64_encode($from_user)."?=";
      $subject = "=?UTF-8?B?".base64_encode($subject)."?=";

      $headers = "From: $from_user <$from_email>\r\n".
               "MIME-Version: 1.0" . "\r\n" .
               "Content-type: text/html; charset=UTF-8" . "\r\n";

     return mail($to, $subject, $message, $headers);
}

function lockCreate() {
    $_SESSION['lock'] = true;
}

function lockRemove() {
    unset($_SESSION['lock']);
}

function lockExists() {
    return isset($_SESSION['lock']);
}

function getToken($length){
     $token = "";
     $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
     $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
     $codeAlphabet.= "0123456789";
     $max = strlen($codeAlphabet); // edited

    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[random_int(0, $max-1)];
    }

    return $token;
}

function hashing($url) {
    return hash('sha256', $url) . strlen($url);
}

function form() {

    $countVideos=count( glob("./*", GLOB_ONLYDIR) );

    echo '<description>The main purpose of this experimental project is to help deaf people, producing automatic subtitles and transcripts.<br /><alert><italic>The speech recognition quality is very variable, some videos will give useful subtitles, others won\'t.</italic></alert><br />This service is free of charge (to prevent abuse only one video is processed at a time).<br />Your privacy is guaranteed because no video remains stored on the server, and no information is collected about you. The generated subtitles are cached on the server for better performance. You can <a href="https://www.informatica-libera.net/content/contatti" target="blog">contact me</a> for any help.<br /><br />';

    if (lockExists())
        echo ("<alert>You have already submitted a video, but its processing is not finished: at the moment, you cannot submit another video. If the error persists, close and reopen the browser</alert>");
    else {
        echo "<form method='post' action=''>";
        echo "<alert>Select the language of the video (*):<br /></alert>";

        echo "<input type='radio' name='language' value='en' /> English<br>";
        echo "<input type='radio' name='language' value='it' /> Italian<br />";
        echo "<input type='radio' name='language' value='fr' /> French<br />";
        echo "<input type='radio' name='language' value='ar' /> Arabic<br />";
        // echo "<input type='radio' name='language' value='el' /> Greek<br />";
        // echo "<input type='radio' name='language' value='pl' /> Polish<br />";

        echo "<input type='text' name='url' size='40' onfocus=\"this.placeholder=''\" onblur=\"this.placeholder='ex.: http://www.mysite.com/video.mp4'\" placeholder='ex.: http://www.mysite.com/video.mp4' /><br/>";

        echo "<br /><img id='captcha' src='/securimage/securimage_show.php' alt='CAPTCHA Image' /><br /><input type='text' name='captcha_code' size='10' maxlength='6' />
<a href='#' onclick=\"document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false\">[ Different Image ]</a><br />";

        echo "<span id='wait'>Please wait and keep this page open... <br />Your request has been queued after other people requests, so it can take a while before being processed!<br />The server process only one video at a time.</span>";
        echo "<input type='submit' value='Autotranscript' id='show_button' onclick='showWait();' /><br />";
        echo "<input type='hidden' name='token' value='" . $_SESSION['token'] . "' />";
        echo "<italic><a href='#comments'>See comments</a></italic><br /><br />";
        echo "<note>(*) Because the already done transcripts are cached, if you specify an already processed video the language you select (and the captcha code) will be ignored</note><br />";
        echo "</form>";
    }

    echo '<br /><strong>Usage: provide the URL of a mp4 video file and select the language.<br />
<green>For example, you can copy the following URLs (and paste them in the above form) and try their automatic transcript:</green>
</strong>
<ol>

<li><italic>(English)</italic> <a href="http://217.61.0.217/en_videolesson_demo_fiveminutes.mp4" target="videolesson"><strong>http://217.61.0.217/en_videolesson_demo_fiveminutes.mp4</strong></a> <italic>(Five minutes videolesson demo)</italic></li>

<li><italic>(English)</italic> <a href="https://www.informatica-libera.net/video/EliPariser_2011-480p-it.mp4" target="blog"><strong>https://www.informatica-libera.net/video/EliPariser_2011-480p-it.mp4</strong></a> <italic>(Ted Talk, see <a href="https://www.informatica-libera.net/content/gli-algoritmi-di-internet-ci-stanno-danneggiando-con-la-personalizzazione-su-misura" target="blog">more info</a>)</italic></li>

<li><italic>(Italian)</italic> <a href="https://www.utiu-students.net/video/video-faq.mp4"  target="videotest"><strong>https://www.utiu-students.net/video/video-faq.mp4</strong></a>  <italic>(Italian video-FAQs for new Uninettuno\'s students)</italic></li>

<li><italic>(Italian)</italic> <a href="https://www.informatica-libera.net/video/docenti_era_digitale.mp4" target="videolesson"><strong>https://www.informatica-libera.net/video/docenti_era_digitale.mp4</strong></a> <italic>(Full Uninettuno videolesson, see <a href="https://www.informatica-libera.net/content/pedagogia-nellera-digitale-con-una-videolezione-di-uninettuno" target="videolesson">more info</a>)</italic></li>

<li><italic>(French)</italic> <a href="http://217.61.0.217/fr_videolesson_demo_fiveminutes.mp4" target="videolesson"><strong>http://217.61.0.217/fr_videolesson_demo_fiveminutes.mp4</strong></a> <italic>(Five minutes French videolesson demo)</italic></li>

<li><italic>(Arabic)</italic> <a href="http://217.61.0.217/arabic-sentences.mp4" target="videolesson"><strong>http://217.61.0.217/arabic-sentences.mp4</strong></a> <italic>(Arabic sentences demo)</italic></li>

</ol>
<br />
Tip 1: You can use <a href="https://alltubedownload.net/" target="alltubedownload">AllTube Download</a> to get the URL of a video embedded in a (public available) webpage<br /><br />
Tip 2: If you are a <strong><a href="https://www.uninettunouniversity.net/" target="Uninettuno">Uninettuno</a> deaf student</strong>, this tool can help you: I\'ll start a discussion in the Students Community<br /><br />
Tip 3: Try to load <strong>only one video at a time</strong> from your browser, otherwise unexpected behaviour can occur<br /><br />
Tip 4: If other people are using this service, your video transcription request will be queued: please be patient<br /><br />
<italic>At present, ' . $countVideos . ' videos have been processed ;-)</italic><br /><br />
</description>';

}

/**
 * Returns the size of a file without downloading it, or -1 if the file
 * size could not be determined.
 *
 * @param $url - The location of the remote file to download. Cannot
 * be null or empty.
 *
 * @return The size of the file referenced by $url, or -1 if the size
 * could not be determined.
 */
function curl_get_file_size( $url ) {
  // Assume failure.
  $result = -1;

  $curl = curl_init( $url );

  // Issue a HEAD request and follow any redirects.
  curl_setopt( $curl, CURLOPT_NOBODY, true );
  curl_setopt( $curl, CURLOPT_HEADER, true );
  curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );

  $data = curl_exec( $curl );
  curl_close( $curl );

  if( $data ) {
    $content_length = "unknown";
    $status = "unknown";

    if( preg_match( "/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches ) ) {
      $status = (int)$matches[1];
    }

    if( preg_match( "/Content-Length: (\d+)/", $data, $matches ) ) {
      $content_length = (int)$matches[1];
    }

    // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
    if( $status == 200 || ($status > 300 && $status <= 308) ) {
      $result = $content_length;
    }
  }

  return $result;
}

function isValidVideoBetter($url) {

    $retvalue = false;

    // get file headers
    $ch = curl_init(str_replace(" ","%20",$url));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);

    // get content type
    $allowed_content_types = array("video/mp4");
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if (in_array($content_type, $allowed_content_types)) {
        $retvalue = true;
    } 

    curl_close($ch);

    return $retvalue;
}

/**
 * Execute the given command by displaying console output live to the user.
 *  @param  string  cmd          :  command to be executed
 *  @return array   exit_status  :  exit status of the executed command
 *                  output       :  console output of the executed command
 */
function liveExecuteCommand($cmd)
{

    while (@ ob_end_flush()); // end all output buffers if any

    echo '<pre>';

    // $proc = popen("$cmd 2>&1 ; echo Exit status : $?", 'r');
    $proc = popen("$cmd 2>&1 ; echo \<\!\-\- Exit status : $? \-\-\>", 'r');

    $live_output     = "";
    $complete_output = "";

    while (!feof($proc))
    {
        $live_output     = fread($proc, 4096);
        $complete_output = $complete_output . $live_output;
        echo "$live_output";
        @ flush();
    }

    pclose($proc);

    echo '</pre>';

    // remove the comment tags
    $complete_output = preg_replace('/<!--/Uis', '', $complete_output);
    $complete_output = preg_replace('/ -->/Uis', '', $complete_output);

    // get exit status
    preg_match('/[0-9]+$/', $complete_output, $matches);

    // return exit status and intended output
    return array (
                    'exit_status'  => intval($matches[0]),
                    'output'       => str_replace("Exit status : " . $matches[0], '', $complete_output)
                 );
}

/*** END OF FUNCTIONS ***/

/*** START OF THE MAIN ***/

if(isset($_POST)){

    // The following if/else are written to get the url, if specified, or to show the form

    if(empty($_POST["url"]) && !isset($_SESSION['url']))
    {
        // First execution - The form wasn't submitted!
        form();
    }
    else
    {
        // The form was submitted!

        if (isset($_POST["url"]) && isset($_POST["language"]) && isset($_POST["token"])) {

            // The user submitted the form with an url to process and a language selected

            $url = $_POST["url"];
            $url = str_replace(" ","%20",$url); // It's to avoid problems with spaces
            $language = $_POST["language"];

            // Security check
            if (!isset($_SESSION['token']) || ($_POST["token"] != $_SESSION['token'] ))
                die ("Invalid Token");

            // Send an email
            sendMail($url);
            

        } else if(isset($_SESSION['url'])) {

            // In this case the page reloaded itself after processing the url
            // The subtitle file "video.srt" is ready

            $url = $_SESSION['url'];

            // Security check
            if (!isset($_SESSION['token'])) 
                die ("Invalid Token");

        } else {

            // Strange situation: this case should not happen

            die ("<error>Error in the URL</error>");
        }

        // The $url variable is setted, so I don't need it any more in the session 
        if(isset($_SESSION['url'])) unset($_SESSION['url']);

        echo "Url of the video:<br /><url><a target='transcript' href='". $url . "'>".$url."</a></url><br /><br />";

        // In the following code, if the video.srt associated to the url exists,
        // it will be displayed, else it will be generated 

        $hash = hashing($url);
        $subtitlesRelativePath = $hash . '/' . $SUBFILENAME;
        $subtitlesFullPath = __DIR__ . '/' . $subtitlesRelativePath;
        $rtlFileFullPath = __DIR__ . '/' . $hash . '/' . "rtl"; // right-to-left language
        
        if (file_exists($subtitlesFullPath)) {

            echo "
            <div style='width:100%; max-width: 600px; margin: 0 auto;'>
            <script type='text/javascript' src='../jwplayer-7.4.4/jwplayer.js'></script>
            <script>jwplayer.key='wVwmSsMpTvGtpLKwzRmgd3/4ELkolqEhSrhE4w==';</script>
	            <div id='BnoNxz4vLJ'>Please wait, I'm loading the video player with the automatically generated subtitles... :-)
	            </div>
            <script type='text/javascript'>
	              jwplayer('BnoNxz4vLJ').setup({
                    width: '100%',
                    aspectratio: '16:9',
                    playlist: [{
                            file: '" . $url . "',
                            image: 'play.png',
                            tracks: [{ 
                                file: '" . $subtitlesRelativePath . "', 
                                kind: 'captions',
                                'default': true 
                            }]
                        }]
	              });
            </script>
            </div>";


            echo "<h2>Subtitles</h2>";
            echo "<a target='transcript' href='$subtitlesRelativePath'>Link to download subtitles</a> (you can use them with VLC or similar video players)<br /><br />Usage: save the <a target='transcript' href='". $url . "'>mp4 file</a> and the <a target='transcript' href='" . $subtitlesRelativePath . "'>video.srt</a> file in the same folder, then open the video and load the subtitles from the video.srt file";
            echo "<h2>Transcript</h2>";
            if (file_exists($rtlFileFullPath)) echo "<style type='text/css'>pre {text-align: right;}</style>";
            $result = liveExecuteCommand($SHOW_TRANSCRIPT_UTILITY . " \"" . $subtitlesFullPath . "\"");
            if($result['exit_status'] === 0){
               // do something if command execution succeeds
            } else {
               die ("<error>I'm sorry: I cannot show the transcript!</show>");
            }
        }
        else {

            // VIDEO PROCESSING (because the video.srt doesn't exist)

            // Captcha check
            if ($securimage->check($_POST['captcha_code']) == false) {
              echo "The security code entered was incorrect.<br /><br />";
              echo "Please go <a href='javascript:history.go(-1)'>back</a> and try again.";
              exit;
            }

            // Use a lock file to allow only one video processing at a time for each session
            // The lock file is removed at the end of file processing
            if (!lockExists())
                lockCreate();
            else
                die ("<error>You have already submitted a video, but its processing is not finished: at the moment, you cannot submit another video. If the error persists, close and reopen the browser</error>");

            echo "Please wait while processing the video...<br /><br />";
            echo "Language selected: " . $language . "<br /><br />";

            $fileSize = curl_get_file_size($url);
            if ($fileSize <= 0) {
                if (lockExists()) lockRemove();
                die("<error>I cannot get the size of the video file, so I won't download it!</error>");
            }
            if ($fileSize > 524288000) {
                if (lockExists()) lockRemove();
                die("<error>The video files is too much big!!! (>500MB)</error>");
            }

            echo "MP4 file size: " . ((int)($fileSize/(1024*1024)*10))/10.0 . "MB <br />";

            if (!isValidVideoBetter($url)) {
                if (lockExists()) lockRemove();
                die("<error>The url you provided is not a valid MP4 video!</error>");
            } else {
                echo "It seems that you provided a valid MP4 video!<br/>";
            }

            $result = liveExecuteCommand($GENERATE_SUBTITLES_UTILITY . " \"" . $url . "\" \"" . $subtitlesFullPath . "\" " . "\"" . $language . "\"");

            // The video processing is terminated,
            // so I can remove the lock and generate a new token
            if (lockExists()) lockRemove(); 
            $_SESSION['token'] = getToken(50);

            if($result['exit_status'] === 0){

                // if Arabic create an "rtl" file to remember that is a right-to-left language
                if ($language == "ar") { 
                    $fileRTL = @fopen("$rtlFileFullPath","x");
                    if($fileRTL)
                    {
                        echo fwrite($fileRTL,"Right-To-Left language"); 
                        fclose($fileRTL); 
                    }
                }

                // save the URL in the session
                $_SESSION['url'] = $url;

                // redirect to the same page
                echo "<script>window.location = \"" . htmlspecialchars($_SERVER['PHP_SELF']) . "\";</script>";
                echo "<a href='". htmlspecialchars($_SERVER['PHP_SELF']) ."'>If the page doesn't reload automatically, click here to download the subtitles and the transcript ;-)</a>";
                
            } else {
               die ("<error>I'm sorry: I cannot generate the subtitles!</show>");
            }

        }
        
    }
}
?>
<script type="text/javascript">
    // Hide submit button after click ;-)
    var button = document.getElementById('show_button')
    button.addEventListener('click',hideshow,false);

    function hideshow() {
        document.getElementById('show_button').style.display = 'block'; 
        this.style.display = 'none'
    }   
</script>
<a id='comments' name='comments'></a><div id="disqus_thread"></div>
<script>
var disqus_config = function () {
this.page.url = "http://217.61.0.217/";  // Replace PAGE_URL with your page's canonical URL variable
this.page.identifier = "autotranscript"; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
};
(function() { // DON'T EDIT BELOW THIS LINE
var d = document, s = d.createElement('script');
s.src = 'https://http-217-61-0-217.disqus.com/embed.js';
s.setAttribute('data-timestamp', +new Date());
(d.head || d.body).appendChild(s);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
                            
</body>
</html>50
