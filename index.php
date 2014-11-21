<?php if(!defined('IS_CMS')) die();

class Formular extends Plugin {

    private $admin_lang;
    private $para;
    private $asta;
    private $submit;
    private $search;
    private $replace;
    private $css_error;
    private $html = "";
    private $fm_name;
    private $fm_session;
    private $mail_subject;
    private $mail_txt;
    private $mail_from;
    private $mail_to;
    private $spam_task;

    function getDefaultSettings() {
        return array(
            "active" => "true",
            "spam_task" => "3 + 7 = 10<br />5 - 3 = 2<br />1 plus 1 = 2<br />17 minus 7 = 10<br />4 * 2 = 8<br />3x3 = 9<br />2 durch 2 = 1<br />8 * 1 = 8<br />10 : 2 = 5<br />4 - 1 = 3",
            "formwaittime" => "15"
        );
    }

    function getContent($value) {

        $this->para = array();
        $this->asta = array();
        $this->submit = array();
        $this->search = array();
        $this->replace = array();
        $this->css_error = array();
        $this->mail_subject = "";
        $this->mail_txt = "";
        $this->mail_from = "";
        $this->mail_to = "";
        $this->spam_task = true;

        if($value and is_file($this->PLUGIN_SELF_DIR."formular/".$value.".php"))
            $this->fm_name = $value;
        elseif($value === false and is_file($this->PLUGIN_SELF_DIR."formular/contact.php"))
            $this->fm_name = "contact";
        else
            return NULL;

        global $CMS_CONF;
        $lang = new Language($this->PLUGIN_SELF_DIR."lang/formular_".$CMS_CONF->get("cmslanguage").".txt");

        $this->fm_session = $this->makeRandomStr();

        $sendtime = 15;
        // Bot-Schutz: Wurde das Formular innerhalb von x Sekunden abgeschickt?
        if(strlen($this->settings->get("formwaittime")) > 0) {
            $sendtime = $this->settings->get("formwaittime");
        }

        $this->html = file_get_contents($this->PLUGIN_SELF_DIR."formular/".$this->fm_name.".php");
        $this->html = preg_replace('/\<\?php.*\?\>[\s]+/Ums',"",$this->html);

        $this->html = preg_replace('/\<\!--.*--\>/Ums', "", $this->html);

        $str_fm_len = strlen("formular_");
        foreach($lang->LANG_CONF->toArray() as $para => $tmp) {
            $t_fm = "{".strtoupper(substr($para,$str_fm_len))."}";
            if(strpos($this->html,$t_fm) !== false)
                $this->html = str_replace($t_fm,$lang->getLanguageHtml($para),$this->html);
        }

        preg_match('/\<mail_subject\>(.*)\<\/mail_subject\>/Ums',$this->html,$this->mail_subject);
        preg_match('/\<mail_txt\>(.*)\<\/mail_txt\>/Ums',$this->html,$this->mail_txt);
        preg_match('/\<mail_from\>(.*)\<\/mail_from\>/Ums',$this->html,$this->mail_from);
        preg_match('/\<mail_to\>(.*)\<\/mail_to\>/Ums',$this->html,$this->mail_to);

        $this->html = str_replace('{URL_SRC}',$this->PLUGIN_SELF_URL."formular/",$this->html);
        $this->html = str_replace('{SENDTIME}',$sendtime,$this->html);

        if(count($this->mail_subject) > 1) {
            $this->html = str_replace($this->mail_subject[0],"",$this->html);
            $this->mail_subject = $this->mail_subject[1];
        }
        if(count($this->mail_txt) > 1) {
            $this->html = str_replace($this->mail_txt[0],"",$this->html);
            $this->mail_txt = $this->mail_txt[1];
        }
        if(count($this->mail_from) > 1 and strlen($this->mail_from[1]) > 5) {
            $this->html = str_replace($this->mail_from[0],"",$this->html);
            $this->mail_from = $this->mail_from[1];
        } else
            $this->mail_from = $this->settings->get("frommail");

        require_once(BASE_DIR_CMS."Mail.php");
        // existiert eine Mailadresse? Wenn nicht: Das Kontaktformular gar nicht anzeigen!
        if(!isMailAddressValid($this->mail_from)) {
            return '<span class="deadlink">'.$lang->getLanguageValue("formular_no_email")."</span>";
        }

        if(count($this->mail_to) > 1 and strlen($this->mail_to[1]) > 5) {
            $this->html = str_replace($this->mail_to[0],"",$this->html);
            $this->mail_to = $this->mail_to[1];
        } else
            $this->mail_to = $this->settings->get("tomail");

        if(strlen($this->mail_to) > 4) {
            if(strpos($this->mail_to,",") > 1) {
                $tmp = explode(",",$this->mail_to);
                $this->mail_to = array();
                foreach($tmp as $m) {
                    $m = trim($m);
                    if(!isMailAddressValid($m))
                        return '<span class="deadlink">'.$lang->getLanguageValue("formular_no_email")."</span>";
                    else
                        $this->mail_to[] = $m;
                }
            } elseif(!isMailAddressValid($this->mail_to)) {
                return '<span class="deadlink">'.$lang->getLanguageValue("formular_no_email")."</span>";
            } else
                $this->mail_to = array($this->mail_to);
        } else
            $this->mail_to = array($this->mail_from);

        $this->makePara();
        $this->replaceName();

        if($this->isSubmit() and !$this->isAsta() and !$this->checkError($sendtime)) {
            global $language;
            $title = "";
            $forename = "";
            $name = "";
            $replay_to = false;

            if(isset($this->para["mail_to"]) and $this->para["mail_to"]["post"] !== false and count($this->para["mail_to"]["value"]) == count($this->mail_to)) {
                $mail_to = array();
                foreach($this->para["mail_to"]["post"] as $key) {
                    if(array_key_exists($key - 1,$this->mail_to))
                        $mail_to[] = $this->mail_to[($key - 1)];
                }
                $this->mail_to = $mail_to;
            }

            if(isset($this->para["type_mail_cc"]) and $this->para["type_mail_cc"]["post"] !== false)
                $this->mail_to[] = $this->para["type_mail"]["post"];

            if(isset($this->para["type_mail"]) and $this->para["type_mail"]["post"])
                $replay_to = $this->para["type_mail"]["post"];

            $this->html = str_replace("fm-success-nodisplay","fm-success",$this->html);
            $this->html = str_replace("fm-box-type","fm-box-success",$this->html);

            $mailcontent = $this->makeMailText();

            $date = strftime($language->getLanguageValue("_dateformat_0"), time());
            $this->mail_subject = str_replace(array("\r\n","\r","\n"),"",$this->mail_subject);
            $this->mail_txt = str_replace(array("\r\n","\r"),"\n",$this->mail_txt);
            $this->mail_txt = str_replace("{MAIL_TEXT}",$mailcontent,$this->mail_txt);


            if(isset($this->para["type_title"]) and $this->para["type_title"]["post"])
                $title = $this->para["type_title"]["post"];
            if(isset($this->para["type_forename"]) and $this->para["type_forename"]["post"])
                $forename = $this->para["type_forename"]["post"];
            if(isset($this->para["type_name"]) and $this->para["type_name"]["post"])
                $name = $this->para["type_name"]["post"];

            $this->mail_txt = str_replace(array("{DATE}","{TITLE}","{FORENAME}","{NAME}"),array($date,$title,$forename,$name),$this->mail_txt);
            $this->mail_subject = str_replace(array("{DATE}","{TITLE}","{FORENAME}","{NAME}"),array($date,$title,$forename,$name),$this->mail_subject);

$test = false;
            foreach($this->mail_to as $to) {
                $rto = "";
                if($replay_to and $to != $replay_to)
                    $rto = $replay_to;
if($test)
    echo "Gesendet an ".$to."<br />\n";
else
                sendMail($this->mail_subject, $this->mail_txt, $this->mail_from, $to, $rto);
            }
if($test) {
    echo "<pre>";
    echo $this->mail_subject."\n";
    echo "------------------------------------\n";
    echo $this->mail_txt."\n";
    echo "</pre>";
}
        }

        $this->setRandomSpamTask();
        $_SESSION[$this->fm_name.'_loadtime'] = time();
        global $CatPage;
        if(is_file($this->PLUGIN_SELF_DIR."formular/".$this->fm_name.".css")) {
            global $syntax;
            $syntax->insert_in_head('<style type="text/css"> @import "'.$this->PLUGIN_SELF_URL.'formular/'.$this->fm_name.'.css"; </style>');
        }
        return '<form accept-charset="'.CHARSET.'" name="form_'.$this->fm_name.'" class="fm-form fm-name-'.$this->fm_name.'" method="post" action="'.$CatPage->get_Href(CAT_REQUEST,PAGE_REQUEST).'">'.$this->html.'</form>';
    }

    private function checkError($sendtime) {
        $this->makePostReplace();
        $error = false;
        $css_search = array("fm-box-type");
        $css_replace = array("fm-box-error");
        if(!$this->spam_task) {
            $error = true;
            $css_search[] = "fm-spamtask-nodisplay";
            $css_replace[] = "fm-spamtask";
        }
        if(isset($_SESSION[$this->fm_name.'_loadtime']) and (time() - $_SESSION[$this->fm_name.'_loadtime'] < $sendtime)) {
            $error = true;
            $css_search[] = "fm-senttoofast-nodisplay";
            $css_replace[] = "fm-senttoofast";
        }
        if(count($this->css_error) > 0) {
            $error = true;
            $css_search[] = "fm-mandatory-nodisplay";
            $css_replace[] = "fm-mandatory";
        }
        if(isset($this->para["type_mail"])
                and $this->para["type_mail"]["post"] !== false
                and !isMailAddressValid($this->para["type_mail"]["post"])) {
            $error = true;
            $css_search[] = "fm-mailvalid-nodisplay";
            $css_replace[] = "fm-mailvalid";
            $css_search[] = "-mailnovalid";
            $css_replace[] = "";
        }
        if(!isset($this->para["type_mail"]["mandatory"])
                and isset($this->para["type_mail"])
                and $this->para["type_mail"]["post"] === false
                and isset($this->para["type_mail_cc"])
                and $this->para["type_mail_cc"]["post"] !== false) {
            $error = true;
            $css_search[] = "fm-mailcopy-nodisplay";
            $css_replace[] = "fm-mailcopy";
            $css_search[] = "-mailnocopy";
            $css_replace[] = "";
        }
        if($error) {
            $this->html = str_replace($this->search,$this->replace,$this->html);
            $this->html = str_replace($this->css_error,"",$this->html);
            $this->html = str_replace($css_search,$css_replace,$this->html);
        }
        return $error;
    }

    private function isSubmit() {
        foreach($this->submit as $name) {
            if(false !== getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post",false))
                return true;
        }
        return false;
    }

    private function isAsta() {
        global $specialchars;
        foreach($this->asta as $name) {
            $tmp = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");
            if(is_array($tmp)) {
                foreach($tmp as $key => $value)
                    $tmp[$key] = $specialchars->rebuildSpecialChars($value,false,false);
            } else
                $tmp = $specialchars->rebuildSpecialChars($tmp,false,false);
            if($this->para[$name]["type"] == "text" or $this->para[$name]["type"] == "textarea") {
                if($this->para[$name]["value"] !== $tmp)
                    return true;
            }
            if($this->para[$name]["type"] == "radio" or $this->para[$name]["type"] == "checkbox") {
                $test = array();
                foreach($this->para[$name]["search"] as $pos => $input) {
                    if(preg_match('#\ checked=["\']checked["\']|\ checked#',$input))
                        $test[] = $this->para[$name]["value"][$pos];
                }
                $test2 = $tmp;
                if($tmp === false)
                    $test2 = array();
                if(count(array_diff($test2,$test)) > 0)
                    return true;
            }
            if($this->para[$name]["type"] == "select" and !isset($this->para[$name]["multiple"])) {
                preg_match_all('/\<option(.*)?>(.*)\<\/option\>/Ums',$this->para[$name]["search"],$option);

                if(strpos($this->para[$name]["search"]," selected") !== false) {
                    foreach($option[0] as $pos => $value) {
                        if(strpos($value," selected") !== false and strpos($value," value=") !== false) {
                            preg_match('/\ value=["\'](.*)["\']/Ums',$value,$tmp1);
                            if(isset($tmp1[1]) and $tmp != $tmp1[1])
                                return true;
                            break;
                        }
                    }
                } else {
                    if(isset($option[2][0]) and $tmp != $option[2][0])
                        return true;
                }
            }

            if($this->para[$name]["type"] == "select" and isset($this->para[$name]["multiple"])) {
                $test = array();
                preg_match_all('/\<option(.*)\<\/option\>/Ums',$this->para[$name]["search"],$option);
                foreach($option[0] as $pos => $input) {
                    if(preg_match('#\ selected=["\']selected["\']|\ selected#',$input))
                        $test[] = $this->para[$name]["value"][$pos];
                }
                $test2 = $tmp;
                if($tmp === false)
                    $test2 = array();
                if(count(array_diff($test2,$test)) > 0)
                    return true;
            }
        }
    }

    private function makeMailText() {
        $txt = "";
        foreach($this->para as $name => $para) {
            if($para["name"] == "SPAM_TASK" or strpos($para["name"],"ASTALAVISTA") !== false)
                continue;
            if(isset($para["class"]) and strpos($para["class"],"fm-no-mtext") !== false)
                continue;
            if(($para["type"] == "text" or $para["type"] == "textarea") and $para["post"]) {
                if(isset($para["id"])) {
                    if(false !== ($tmp = $this->findMailTextTag("for",$para["id"]))) {
                        $txt .= $tmp."\n";
                        $txt .= "\t".str_replace("\n","\n\t",$para["post"])."\n";
                    } else
                        $txt .= $para["post"]."\n";
                }
                $txt .= "\n";
            }

            if($para["type"] == "radio" and $para["post"]) {
                if(isset($para["id"])) {
                    $tab = "";
                    if(false !== ($tmp = $this->findMailTextTag("id",'title_for_'.$para["name"]))) {
                        $txt .= $tmp."\n";
                        $tab = "\t";
                    }
                    foreach($para["value"] as $pos => $val) {
                        $tmp = "( ) ";
                        if($val == $para["post"])
                            $tmp = "(X) ";

                        $txt .= $tab.$tmp.$this->findMailTextTag("for",$para["id"][$pos],$val)."\n";
                    }
                }
                $txt .= "\n";
            }

            if($para["type"] == "checkbox" and $para["post"]) {
                if(isset($para["id"])) {
                    $tab = "";

                    if(false !== ($tmp = $this->findMailTextTag("id",'title_for_'.$para["name"]))) {
                        $txt .= $tmp."\n";
                        $tab = "\t";
                    }
                    $value = $para["value"];
                    if(!is_array($value))
                        $value = array($value);
                    foreach($value as $pos => $val) {
                        $tmp = "[ ] ";
                        if(in_array($val,$para["post"]))
                            $tmp = "[X] ";
                        $id = $para["id"];
                        if(is_array($para["id"]))
                            $id = $para["id"][$pos];

                        $txt .= $tab.$tmp.$this->findMailTextTag("for",$id,$val)."\n";
                    }
                }
                $txt .= "\n";
            }

            if($para["type"] == "select" and $para["post"]) {
                if(isset($para["id"])) {
                    $tab = "";
                    if(false !== ($tmp = $this->findMailTextTag("for",$para["id"]))) {
                        $txt .= $tmp."\n";
                        $tab = "\t";
                    }

                    foreach($para["post"] as $pos => $val) {
                        if(false !== ($tmp = $this->findMailTextTag("value",$val,false,$para["search"]))) {
                            $txt .= $tab.$tmp."\n";
                        }
                    }
                }
                $txt .= "\n";
            }
        }
        return $txt;
    }

    private function findMailTextTag($attr,$value,$alternative = false,$content = false) {
        if(false !== ($mail_tag = $this->findTagByAttrValue($attr,$value,$content))) {
            if(false !== ($no_text = $this->findTagByAttrValue("class","fm-no-mtext",$mail_tag))) {
                $mail_tag = str_replace($no_text,"",$mail_tag);
            }
            if(strlen($mail_tag) > 6)
                return strip_tags(str_replace(array("<br />","<br>"),"\n",str_replace(array("\r\n","\r","\n"),"",$mail_tag)));
        }
        if($alternative)
            return trim($alternative);
        return false;
    }

    private function findTagByAttrValue($attr,$value,$content = false) {
        if(!$content)
            $content = $this->html;
        $find = array();

        # open close Tags
        if($attr == "class")
            preg_match_all('#<([a-z0-9]+)[^>]*?'.$attr.'=["\'][^"\']*?'.$value.'[^"\']*?["\'][^>]*?>(.*?)</\1>#is', $content, $match);
        else
            preg_match_all('#<([a-z0-9]+)[^>]*?'.$attr.'=["\']'.$value.'["\'][^>]*?>(.*?)</\1>#is', $content, $match);
        if(isset($match[0][0])) {
            foreach($match[0] as $key => $tmp) {
                $start_pos = strpos($content,$match[0][$key]);
                $start_ofs = $start_pos + strlen($match[0][$key]);
                $close_tag = '</'.$match[1][$key].'>';
                $op_items = substr_count($match[0][$key], '<'.$match[1][$key]);
                $cl_items = substr_count($match[0][$key], $close_tag);
                if($op_items > $cl_items) {
                    for($i = $cl_items;$i < $op_items;$i++) {
                        $start_ofs = (strpos($content,$close_tag,$start_ofs) + strlen($close_tag));
                    }
                    $match[0][$key] = substr($content,$start_pos,($start_ofs - $start_pos));
                }
            }
            $find = $match[0];
        }
        # no open close Tags
        if($attr == "class")
            preg_match_all('#<(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr){1,1}[^>]*?'.$attr.'=["\'][^"\']*?'.$value.'[^"\']*?["\'][^>]*?>#is', $content, $match);
        else
            preg_match_all('#<(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr){1,1}[^>]*?'.$attr.'=["\']'.$value.'["\'][^>]*?>#is', $content, $match);
        if(isset($match[0][0])) {
            if(count($find) > 0)
                $find = array_merge($find,$match[0]);
            else
                $find = $match[0];
        }
        # nur eins gefunden als string zurück
        if(count($find) == 1)
            return $find[0];
        elseif(count($find) > 0)
            return $find;
        return false;
    }

    private function makePostReplace() {
        foreach($this->para as $name => $para) {
            if($para["name"] == "SPAM_TASK")
                $this->input_SpamTask($name);
            elseif($para["type"] == "text")
                $this->input_text($name);
            elseif($para["type"] == "textarea")
                $this->input_textarea($name);
            elseif($para["type"] == "radio")
                $this->input_radio($name);
            elseif($para["type"] == "select")
                $this->input_select($name);
            elseif($para["type"] == "checkbox")
                $this->input_checkbox($name);
        }
    }

    private function input_SpamTask($name) {
        global $specialchars;
        $value = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");
        $value = $specialchars->rebuildSpecialChars($value, false, false);
        if($_SESSION['spam_task'.$this->fm_name] !== $value) {
            $this->setMandatoryError($name);
            $this->spam_task = false;
        }
    }

    private function input_text($name) {
        global $specialchars;
        $value = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");
        $value = trim($specialchars->rebuildSpecialChars($value, false, false));
        $replace = $this->para[$name]["search"];
        $replace = preg_replace('/value=(["\']).*["\']/U','value=${1}'.$value.'${1}',$replace);
        $this->search[] = $this->para[$name]["search"];
        $this->replace[] = $replace;
        if(strlen($value) < 1 or $value === $this->para[$name]["value"]) {
            $this->setMandatoryError($name);
            $this->para[$name]["post"] = false;
        } else
            $this->para[$name]["post"] = $value;
    }

    private function input_checkbox($name) {
        global $specialchars;
        $value = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");
        $check = ' checked="checked"';
        $replace = $this->para[$name]["search"];
        if($value !== false and is_array($value)) {
            $replace = str_replace($check,"",$replace);
            $tmp_post = array();
            foreach($value as $val) {
                $val = $specialchars->rebuildSpecialChars($val, false, false);
                if(in_array($val,$this->para[$name]["value"])) {
                    $replace = preg_replace('/value=(["\']'.$val.'["\'])/U','value=${1}'.$check,$replace);
#                    $replace = preg_replace('/value=(["\'])'.$val.'["\']/U','value=${1}'.$val.'${1}'.$check,$replace);
                    $tmp_post[] = $val;
                }
            }
            if(count($tmp_post) > 0) {
                $this->para[$name]["post"] = $tmp_post;
            } else {
                $this->para[$name]["post"] = false;
            }
        } elseif($value !== false and ($value = $specialchars->rebuildSpecialChars($value, false, false)) === $this->para[$name]["value"]) {
            $replace = str_replace($check,"",$replace);
            $replace = preg_replace('#([\ ]?[/]?\>)$#U',$check.'$1',$replace);
            $this->para[$name]["post"] = array($value);
        } else {
            $this->para[$name]["post"] = false;
        }

        if($this->para[$name]["post"] === false)
            $this->setMandatoryError($name);

        if(is_array($this->para[$name]["search"])) {
            $this->search = array_merge($this->search, $this->para[$name]["search"]);
            $this->replace = array_merge($this->replace, $replace);
        } else {
            $this->search[] = $this->para[$name]["search"];
            $this->replace[] = $replace;
        }
    }

    private function input_radio($name) {
        $value = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");

        $check = ' checked="checked"';
        $replace = $this->para[$name]["search"];
        if(is_array($value) and count($value) == 1 and in_array($value[0],$this->para[$name]["value"])) {
            $replace = str_replace($check,"",$replace);
            global $specialchars;
            $value = $specialchars->rebuildSpecialChars($value[0], false, false);
            $this->para[$name]["post"] = $value;
            $replace = preg_replace('/value=(["\']'.$value.'["\'])/U','value=${1}'.$check,$replace);
#            $replace = preg_replace('/value=(["\'])'.$value.'["\']/U','value=${1}'.$value.'${1}'.$check,$replace);
        } else {
            $this->setMandatoryError($name);
            $this->para[$name]["post"] = false;
        }
        $this->search = array_merge($this->search, $this->para[$name]["search"]);
        $this->replace = array_merge($this->replace, $replace);
    }

    private function input_select($name) {
        global $specialchars;
        $value = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");

        $check = ' selected="selected"';
        $replace = $this->para[$name]["search"];

        if(!isset($this->para[$name]["multiple"]) and !is_array($value)) {
            $value = $specialchars->rebuildSpecialChars($value, false, false);
            if(in_array($value,$this->para[$name]["value"])) {
                $replace = str_replace($check,"",$replace);
                $this->para[$name]["post"] = array($value);
                $replace = preg_replace('/value=(["\']'.$value.'["\'])/U','value=${1}'.$check,$replace);
            } else {
                $this->para[$name]["post"] = false;
            }
        } elseif(isset($this->para[$name]["multiple"]) and is_array($value)) {
            $replace = str_replace($check,"",$replace);
            $tmp_post = array();
            foreach($value as $val) {
                $val = $specialchars->rebuildSpecialChars($val, false, false);
                if(in_array($val,$this->para[$name]["value"])) {
                    $replace = preg_replace('/value=(["\']'.$val.'["\'])/U','value=${1}'.$check,$replace);
#                    $replace = preg_replace('/value=(["\'])'.$val.'["\']/U','value=${1}'.$val.'${1}'.$check,$replace);
                    $tmp_post[] = $val;
                }
            }
            if(count($tmp_post) > 0) {
                $this->para[$name]["post"] = $tmp_post;
            } else {
                $this->para[$name]["post"] = false;
            }
        } else {
            $this->para[$name]["post"] = false;
        }
        if($this->para[$name]["post"] === false)
            $this->setMandatoryError($name);
        $this->search[] = $this->para[$name]["search"];
        $this->replace[] = $replace;
    }

    private function input_textarea($name) {
        global $specialchars;
        $value = getRequestValue($this->fm_session.$this->para[$name]["session_pos"],"post");
        $replace = $this->para[$name]["search"];
        $value = $specialchars->rebuildSpecialChars($value, false, true);
        $replace = str_replace($this->para[$name]["value"].'</textarea>',$value.'</textarea>',$replace);
        $this->search[] = $this->para[$name]["search"];
        $this->replace[] = $replace;
        if(strlen($value) < 1) {
            $this->para[$name]["post"] = false;
            $this->setMandatoryError($name);
        } else
            $this->para[$name]["post"] = $value;
    }

    private function setMandatoryError($name) {
        if(isset($this->para[$name]["class"]) and !array_key_exists($this->para[$name]["name"],$this->asta) and preg_match('/-fm-([\d]+)-ma/U',$this->para[$name]["class"],$match)) {
            $this->css_error[] = $match[0];
            if($this->para[$name]["name"] == "type_mail")
                $this->para[$name]["mandatory"] = true;
        }
    }

    private function replaceName() {
        $new_fm_session = $this->makeRandomStr(true);
        foreach($this->para as $key_name => $para) {
            $name = $new_fm_session.$para["session_pos"];
            if($para["type"] == "radio"
                    or ($para["type"] == "checkbox" and is_array($para["value"]))
                    or ($para["type"] == "select" and isset($para["multiple"]) and $para["multiple"] == "multiple"))
                $name = $new_fm_session.$para["session_pos"]."[]";

            $replace = preg_replace('/name=(["\'])'.$para["name"].'["\']/U','name=${1}'.$name.'${1}',$para["search"]);

            $this->html = str_replace($para["search"],$replace,$this->html);
            $this->para[$key_name]['search'] = $replace;
        }
    }

    private function makePara() {
        preg_match_all("/<input(.*)>|<select(.*)\/select>|<textarea(.*)\/textarea>/Ums", $this->html, $syntax);
        foreach($syntax[0] as $spos => $val) {
            preg_match_all('/([\w]*)=["\'](.*)["\']/Ums', $val, $attribute);
            if(!isset($attribute[1]) and !isset($attribute[2]))
                continue;
            if(false === ($name_pos = array_search('name', $attribute[1]))) {
                continue;
            }

            $name = $attribute[2][$name_pos];
            if(substr($name,0,11) == "ASTALAVISTA") {
                $this->asta[$name] = $name;
            }
            if(substr($name,0,6) == "SUBMIT") {
                $this->submit[] = $name;
            }

            if(strtolower(substr($val,0,7)) == "<select")
                $this->para[$name]["type"] = "select";
            elseif(strtolower(substr($val,0,9)) == "<textarea") {
                $this->para[$name]["type"] = "textarea";
                preg_match('/\<textarea.*\>(.*)\<\/textarea\>/Ums',$val,$tmp);
                $this->para[$name]["value"] = $tmp[1];
            } elseif(false !== ($type_pos = array_search('type', $attribute[1])))
                $this->para[$name]["type"] = $attribute[2][$type_pos];
            else
                continue;

            if(!array_key_exists($name,$this->para))
                $this->para[$name] = array();

            if(array_key_exists("search",$this->para[$name]) and is_array($this->para[$name]["search"]))
                $this->para[$name]["search"][] = $val;
            elseif(array_key_exists("search",$this->para[$name]) and !is_array($this->para[$name]["search"]))
                $this->para[$name]["search"] = array($this->para[$name]["search"],$val);
            else
                $this->para[$name]["search"] = $val;

            foreach($attribute[1] as $pos => $key) {
                if($key != "id" and $key != "value" and $key != "name" and $key != "class" and $key != "multiple")
                    continue;

                if(($key == "value" or $key == "id") and array_key_exists($key,$this->para[$name]) and is_array($this->para[$name][$key]))
                    $this->para[$name][$key][] = $attribute[2][$pos];
                elseif(($key == "value" or $key == "id") and array_key_exists($key,$this->para[$name]) and !is_array($this->para[$name][$key]))
                    $this->para[$name][$key] = array($this->para[$name][$key],$attribute[2][$pos]);
                else
                    $this->para[$name][$key] = $attribute[2][$pos];
            }

            if($this->para[$name]["type"] == "select" and false !== strpos($val," multiple"))
                $this->para[$name]["multiple"] = "multiple";

            # wenn nee Selectbox nur eine eintrag hat
            if($this->para[$name]["type"] == "select" and !is_array($this->para[$name]["value"]))
                $this->para[$name]["value"] = array($this->para[$name]["value"]);

            $this->para[$name]["session_pos"] = $spos;
        }
    }

    private function makeRandomStr($new = false) {
        if(!$new and isset($_SESSION['FM_INPUT_'.$this->fm_name]))
            return $_SESSION['FM_INPUT_'.$this->fm_name];

        $xyz = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $tmp = md5(microtime());
        $tmp = str_split($tmp);
        shuffle($tmp);
        $_SESSION['FM_INPUT_'.$this->fm_name] = $xyz[(rand(0,(strlen($xyz) - 1)))].implode("",$tmp);
        return $_SESSION['FM_INPUT_'.$this->fm_name];
    }

    private function setRandomSpamTask() {
        if(strpos($this->html,"{SPAM_TASK}") === false)
            return;
        $tmp_calcs = explode("<br />",$this->settings->get("spam_task"));
        foreach($tmp_calcs as $zeile) {
            $tmp_z = explode(" = ",$zeile);
            if(isset($tmp_z[0]) and isset($tmp_z[1]) and !empty($tmp_z[0]) and !empty($tmp_z[1]))
                $contactformcalcs[$tmp_z[0]] = $tmp_z[1];
        }
        $tmp = array_keys($contactformcalcs);
        $randnum = rand(0, count($contactformcalcs)-1);
        # keine gleiche frage benutzen
        if(count($tmp_calcs) > 1 and isset($_SESSION['spam_task'.$this->fm_name]) and $_SESSION['spam_task'.$this->fm_name] === $contactformcalcs[$tmp[$randnum]]) {
            $this->setRandomSpamTask();
            return;
        }
        $_SESSION['spam_task'.$this->fm_name] = $contactformcalcs[$tmp[$randnum]];
        $this->html = str_replace("{SPAM_TASK}",$tmp[$randnum],$this->html);
    }

    function getConfig() {

        $config = array();

        $tmpl_formular = '<div style="margin-left:2em;color:red;font-weight:bold;">'.$this->admin_lang->getLanguageValue("noformularfiles").'</div>';
        $formular = array();
        if(is_dir($this->PLUGIN_SELF_DIR."formular/") and false !== ($dir = scandir($this->PLUGIN_SELF_DIR."formular/"))) {
            foreach($dir as $file) {
                if($file[0] != "." and is_file($this->PLUGIN_SELF_DIR."formular/".$file) and substr($file,-4) === ".php") {
                    $formular[] .= substr($file,0,-4);
                }
            }
            if(count($formular) > 0)
                $tmpl_formular = '<div style="margin-left:2em;">'.implode(", ",$formular).'</div>';
        }

        $config['spam_task'] = array(
            "type" => "textarea",
            "rows" => "10",
            "description" => $this->admin_lang->getLanguageValue("spam_task"),
            "template" => $this->admin_lang->getLanguageValue("formularfiles")
                    .$tmpl_formular.'</div>
                </li>
                <li class="mo-in-ul-li mo-inline ui-widget-content ui-corner-all ui-helper-clearfix">
                    <div class="mo-in-li-l">{spam_task_description}</div>
                    <div class="mo-in-li-r">{spam_task_textarea}'
        );

        $config['formwaittime']  = array(
            "type" => "text",
            "description" => $this->admin_lang->getLanguageValue("formwaittime"),
            "maxlength" => "4",
            "size" => "3",
            "regex" => "/^[\d]+?$/",
            "regex_error" => $this->admin_lang->getLanguageValue("formwaittime_error")
        );

        $config['frommail']  = array(
            "type" => "text",
            "description" => $this->admin_lang->getLanguageValue("frommail"),
            "maxlength" => "100"
#            "regex" => "/^(.+@.+\..+)?$/",
#            "regex_error" => $this->adminLang->getLanguageValue("regex_error_mail")
        );

        $config['tomail']  = array(
            "type" => "text",
            "description" => $this->admin_lang->getLanguageValue("tomail"),
            "maxlength" => "255",
            "template" => '{tomail_description}<br />{tomail_text}'
        );

        return $config;
    }

    function getInfo() {
         global $ADMIN_CONF;
         $this->admin_lang = new Language($this->PLUGIN_SELF_DIR."lang/admin_".$ADMIN_CONF->get("language").".txt");

        $info = array(
            "<b>Formular</b> Revision: 9",
            "2.0",
            $this->admin_lang->getLanguageValue("info",$this->PLUGIN_SELF_URL),
            "stefanbe",
            array("http://www.mozilo.de/forum/index.php?action=media","Templates und Plugins"),
            array('{Formular|Formulardatei}' => 'Formular')
        );
        return $info;
    }
}

?>