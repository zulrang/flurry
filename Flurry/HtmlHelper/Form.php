<?php

namespace Flurry\HtmlHelper;

class Form {
    
    public function lookupWithBlank($name, $set, $selected='') {
        $id = $name;

        if(preg_match('/\]$/', $id)) {
            $id = preg_replace('/\[/', '_', $id);
            $id = preg_replace('/\]/', '', $id);
        }

        $output = "<select name='$name' id='$id'>\n";
        $output .= "<option value=''>-</option>";
        foreach($set as $k => $v) {
            $sel = ($k == $selected) ? " selected='selected'" : '';
            $output .= "<option value='$k'$sel>$v</option>\n";
        }
        $output .= "</select>";
        return $output;
    }

    public function lookup($name, $set, $selected='') {
        $id = $name;

        if(preg_match('/\]$/', $id)) {
            $id = preg_replace('/\[/', '_', $id);
            $id = preg_replace('/\]/', '', $id);
        }

        $output = "<select name='$name' id='$id'>\n";
        foreach($set as $k => $v) {
            $sel = ($k == $selected) ? " selected='selected'" : '';
            $output .= "<option value='$k'$sel>$v</option>\n";
        }
        $output .= "</select>";
        return $output;
    }

    public function datebox($params) {
        
        if(!isset($params['id'])) $params['id'] = $params['name'];

        if(preg_match('/\]$/', $params['id'])) {
            $params['id']   = preg_replace('/\[/', '_', $params['id']);
            $params['id']   = preg_replace('/\]/', '', $params['id']);
        }

        if(!isset($params['value'])) $params['value'] = '';

        return "
        <div class='date_field_container'>
        <input type='hidden' name='${params['name']}' id='${params['id']}' value='${params['value']}'/>
        <span class='date_field' id='${params['id']}_display'>${params['value']}&nbsp;</span><span id='${params['id']}_trigger' class='calicon'>&nbsp;</span>
        <script>
            Calendar.setup(
            {
                displayArea : '${params['id']}_display',
                inputField  : '${params['id']}',         // ID of the input field
                ifFormat    : '%d-%b-%Y',    // the date format
                daFormat    : '%d-%b-%Y',    // the date format
                button      : '${params['id']}_trigger',       // ID of the button
                weekNumbers : false
            }
            );
        </script>&nbsp;<!-- fixes empty div float problem -->
        </div>
        ";
    }
}
