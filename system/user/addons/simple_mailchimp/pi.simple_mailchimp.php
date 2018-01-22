<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'simple_mailchimp/config.php';

/**
 * Plugin Info
 *
 * @var array
 */
$plugin_info = array(
    'pi_name'           => SIMPLE_MAILCHIMP_NAME,
    'pi_version'        => SIMPLE_MAILCHIMP_VERSION,
    'pi_author'         => 'Jeremy Worboys',
    'pi_author_url'     => 'http://jeremyworboys.com',
    'pi_description'    => 'A simple way to display a MailChimp sign-up form.',
    'pi_usage'          => Simple_mailchimp::usage()
);


/**
 * Simple MailChimp
 *
 * @package    simple_mailchimp
 * @author     Jeremy Worboys <jw@jeremyworboys.com>
 * @link       http://jeremyworboys.com/add-ons/simple-mailchimp
 * @copyright  Copyright (c) 2013 Jeremy Worboys
 */
class Simple_mailchimp {

    // Mailchimp class
    private $MC;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->EE =& get_instance();
        $this->EE->load->library('form_validation');

        // Fetch parameters and set defaults
        $api_key            = $this->EE->TMPL->fetch_param('api_key');
        $list_id            = $this->EE->TMPL->fetch_param('list_id');
        $form_name          = $this->EE->TMPL->fetch_param('form_name', 'simple_mailchimp');
        $return             = $this->EE->TMPL->fetch_param('return');
        $browser_validate   = $this->EE->TMPL->fetch_param('browser_validate', 'no') === 'yes';
        $this->w3c_validate = $this->EE->TMPL->fetch_param('w3c_validate', 'no') === 'yes';
        $error_delimiters   = $this->EE->TMPL->fetch_param('error_delimiters', '<span class="error">|</span>');
        $email_field        = $this->EE->TMPL->fetch_param('email_field', 'EMAIL');
        $double_optin       = $this->EE->TMPL->fetch_param('double_optin', 'yes') === 'yes';
        $tagdata            = $this->EE->TMPL->tagdata;
        $this->success      = FALSE;

        // Set global error delimiters
        $error_delimiters = explode('|', $error_delimiters);
        $this->EE->form_validation->set_error_delimiters($error_delimiters[0], $error_delimiters[1]);

        // Bring in MailChimp API
        require_once(PATH_THIRD.'simple_mailchimp/libraries/MCAPI.class.php');
        $this->MC = new MCAPI($api_key);

        // Get Mailchimp fields
        $mc_fields = $this->get_mc_fields($list_id);

        // Check to see if the form has been submitted
        if (!empty($_POST) AND $this->EE->input->post(md5($form_name), true)) {
            // Prepare validation rules
            foreach ($mc_fields as $field) {
                // Build validation rule array
                $validation = array();
                $validation[] = 'trim';
                if ($field['req']) {
                    $validation[] = 'required';
                }
                if ($field['field_type'] === 'email') {
                    $validation[] = 'valid_email';
                }
                $validation[] = 'xss_clean';
                // Set validation rule
                $this->EE->form_validation->set_rules($field['tag'], $field['name'], implode('|', $validation));
            }

            // Check if form data was valid
            if ($this->EE->form_validation->run()) {
                // Awesome, let's build the merge vars array
                $merge_vars = array();
                foreach ($mc_fields as $field) {
                    $tag = $field['tag'];
                    if (isset($_POST[$tag])) {
                        $merge_vars[$tag] = $this->EE->input->post($tag, TRUE);
                    }
                }
                // Finally subscribe the user
                $this->MC->listSubscribe($list_id, $merge_vars[$email_field], $merge_vars, 'html', $double_optin);

                // Redirect to the "return" path
                if ($return) {
                    $return = $this->EE->functions->create_url($return);
                    $this->EE->functions->redirect($return);
                }
                $this->success = TRUE;
            }
            // Otherwise, continue displaying the page
        }

        // Handle conditionals early
        $cond['success'] = $this->success;
        $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

        // Prepare the opening form tag
        $form_details = array();
        $form_details['action']        = $this->EE->functions->fetch_current_uri();
        $form_details['name']          = $form_name;
        $form_details['id']            = $this->EE->TMPL->form_id;
        $form_details['class']         = $this->EE->TMPL->form_class;
        $form_details['hidden_fields'] = array(md5($form_name) => '1');

        // Generate the output
        $output  = $this->EE->functions->form_declaration($form_details);
        if (!$browser_validate) {
            $parts = explode('>', $output, 2);
            $parts[0] .= ' novalidate="novalidate"';
            $output = implode('>', $parts);
        }
        $output .= $this->parse_tagdata($tagdata, $mc_fields);
        $output .= '</form>';

        // Send to browser
        $this->return_data = $output;
    }

// -----------------------------------------------------------------------------

    /**
     * Parse Tag Data
     *
     * @param  string Raw tagdata
     * @param  array  Merge fields from MailChimp
     * @return string Parsed tagdata
     */
    private function parse_tagdata($tagdata, $mc_fields)
    {
        // Remap fields so they can be looked up by tag name
        // Also construct the error conditionals so we don't need two loops
        $cond = array();
        $map_fields = array();
        foreach ($mc_fields as $field) {
            $tag = $field['tag'];
            $cond["error:{$tag}"] = !!form_error($tag);
            $map_fields[$tag] = $field;
        }

        // Parse conditionals
        $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

        // Loop over all single var tags
        foreach ($this->EE->TMPL->var_single as $raw_tag => $val) {
            // Setup tag parsing
            $args       = $this->EE->functions->assign_parameters($raw_tag);
            $args       = $args ? $args : array();
            $key        = array_shift(explode(' ', $raw_tag));
            $attr       = array();
            $params     = array();
            $merge      = false;
            $var_tag    = false;
            $parsed_var = '';

            // {label:MERGE}
            if (substr($key, 0, 6) === 'label:') {
                $var_tag = 'label';
                $merge = substr($key, 6);
                unset($args['attr:for']);
            }
            // {merge:MERGE}
            elseif (substr($key, 0, 6) === 'merge:') {
                $var_tag = 'merge';
                $merge = substr($key, 6);
                unset($args['attr:name'], $args['attr:id'], $args['attr:required']);
            }
            // {error:MERGE}
            elseif (substr($key, 0, 6) === 'error:') {
                $var_tag = 'error';
                $merge = substr($key, 6);
            }
            // {submit}
            elseif (substr($key, 0, 6) === 'submit') {
                $var_tag = 'submit';
                unset($args['attr:type']);
                $params['type'] = 'input';
                $params['value'] = 'Subscribe';
            }

            // Skip undefined merge fields
            if ($merge !== false && !isset($map_fields[$merge])) {
                continue;
            }

            // Separate attributes from params
            foreach ($args as $key => $value) {
                if (substr($key, 0, 5) === 'attr:') {
                    $attr[substr($key, 5)] = $value;
                }
                else {
                    $params[$key] = $value;
                }
            }

            // Extract MERGE field
            if (in_array($var_tag, array('label', 'merge'))) {
                // $name, $req, $field_type, $public, $show, $order, $default, $helptext, $size, $tag
                extract($map_fields[$merge]);
            }

            // Build tag replacement
            switch ($var_tag) {
                // {label:MERGE tag}
                case 'label':
                    // Set defaults from extracted MC field
                    switch ($field_type) {
                        case 'address':
                        case 'birthday':
                        case 'radio':
                            $tag_attrs = array();
                            break;

                        default:
                            $tag_attrs = array(
                                "for=\"{$tag}\""
                            );
                            break;
                    }
                    // Add user specified attributes
                    foreach ($attr as $key => $val) {
                        $tag_attrs[] = "{$key}=\"{$val}\"";
                    }
                    if (!isset($params['text'])) {
                        $params['text'] = $name;
                    }
                    // Combine into tag
                    $parsed_var .= '<label '.implode(' ', $tag_attrs).">{$params['text']}</label>";
                    break;

                // {merge:MERGE tag}
                case 'merge':
                    // Set defaults from extracted MC field
                    $tag_attrs = array(
                        "id=\"{$tag}\"",
                    );
                    // Add required if required
                    if ($req) {
                        $tag_attrs[] = "required=\"required\"";
                    }
                    // Add user specified attributes
                    foreach ($attr as $key => $val) {
                        $tag_attrs[] = "{$key}=\"{$val}\"";
                    }

                    // Add value if previously set (i.e. when showing an error)
                    $previous = $this->EE->input->post($tag);

                    // Cast field typee
                    if ($field_type === 'phone')    $field_type = 'tel';
                    if ($field_type === 'zip')      $field_type = 'text';
                    if ($field_type === 'imageurl') $field_type = 'url';

                    // Build field tag
                    switch ($field_type) {
                        case "date":
                        case "email":
                        case "number":
                        case "tel":
                        case "text":
                        case "url":
                            $tag_attrs[] = "type=\"{$field_type}\"";
                            if (!$this->w3c_validate) {
                                $tag_attrs[] = "autocapitalize=\"off\"";
                                $tag_attrs[] = "autocorrect=\"off\"";
                            }
                            $input_var = form_input($tag, $previous, implode(' ', $tag_attrs));
                            $parsed_var .= preg_replace('/ type="text"/', '', $input_var, 1);
                            break;

                        case "radio":
                            foreach ($choices as $value) {
                                $attrs = implode(' ', $tag_attrs);
                                $attrs = str_replace("id=\"{$tag}\"", '', $attrs);
                                $parsed_var .= '<br /><label>'.form_radio($tag, $value, ($value === $previous), $attrs).'&nbsp;&nbsp;'.$value.'</label>';
                            }
                            break;

                        case "dropdown":
                            $parsed_var .= form_dropdown($tag, $choices, $previous, implode(' ', $tag_attrs));
                            break;

                        case "birthday":
                        case "address":
                            $parsed_var .= "<span style=\"color:red; font-weight:bold;\">WARNING: The MailChimp {$field_type} field type is not currently supported.</span>";
                            break;
                    }

                    break;

                // {error:MERGE tag}
                case 'error':
                    // Create tag
                    $parsed_var .= form_error($tag);
                    break;

                // {submit}
                case 'submit':
                    // Set defaults from extracted MC field
                    $tag_attrs = array(
                        "type=\"submit\""
                    );
                    // Add user specified attributes
                    foreach ($attr as $key => $val) {
                        $tag_attrs[] = "{$key}=\"{$val}\"";
                    }
                    // Determine output type
                    if ($params['type'] === 'button') {
                        // Combine into tag
                        $parsed_var .= '<button '.implode(' ', $tag_attrs).">{$params['value']}</button>";
                    }
                    else {
                        $tag_attrs[] = "value=\"{$params['value']}\"";
                        // Combine into tag
                        $parsed_var .= '<input '.implode(' ', $tag_attrs)." />";
                    }
                    break;
            }

            // Swap out parsed variable
            $tagdata = $this->EE->TMPL->swap_var_single($raw_tag, $parsed_var, $tagdata);
        }

        return $tagdata;
    }

// -----------------------------------------------------------------------------

    /**
     * Get Mailchimp data from cache or remote
     *
     * @param  string ID of the list to get the fields for
     * @return string How to use this plugin
     */
    private function get_mc_fields($list_id)
    {
        $mc_fields = false;

        // @FIXME This is super dirty caching, but it will do until v2.0
        $cache_path = dirname(__FILE__).'/_cache';
        if (!file_exists($cache_path)) {
            mkdir($cache_path);
        }

        $cache_file = $cache_path.'/'.md5('simple_mailchimp::mc_fields_'.$list_id);
        if (file_exists($cache_file) && (filemtime($cache_file) - time()) < 60) {
            $mc_fields = @unserialize(file_get_contents($cache_file));
        }

        if (!$mc_fields) {
            $mc_fields = $this->MC->listMergeVars($list_id);
            file_put_contents($cache_file, serialize($mc_fields));
        }

        return $mc_fields;
    }

// -----------------------------------------------------------------------------

    /**
     * Usage
     *
     * @return string How to use this plugin
     */
    public static function usage() 
    {
        ob_start(); ?>

Simple MailChimp
===========================

There is only one tag to embed a MailChimp for on your website:

`{exp:simple_mailchimp}`


Parameters
===========================

The tag has the following possible parameters:

- `api_key` - Your API key.
- `list_id` - The ID of the list you would like to subscribe users to.
- `form_name` - A unique name for this form.
- `return` - The path to the page to display on a successful submission.
- `error_delimiters` - How the error fields are outputted.
- `form_class` - The class to be applied to the form element.
- `form_id` - The ID to be applied to the form element.
- `email_field` - The merge field that contains the users email. (Default "EMAIL")
- `browser_validate` - If anything except "yes" the browser validation will be suppressed. (Default "no")
- `w3c_validate` - If "yes" helpful (but invalid) input attributes will be suppressed. (Default "no")
- `double_optin` - Flag to control whether a double opt-in confirmation message is sent. (Default "yes")


Single Variables
===========================

{label:MERGE}
---------------------------

The `{label:MERGE}` variable displays a label where `MERGE` is the merge tag for
that field (e.g. `{label:EMAIL}`).

The `{label:MERGE}` variable accepts a number of parameters as follows:

- `text` - The text to be displayed between `label` tags. (Default The field name
  specified in MailChimp)
- `attr:ATTR` - Where `ATTR` is any HTML attribute that will be applied to the
  opening label tag. (e.g. `attr:class="form-label"` will apply a class of
  `form-label` to the tag) Some attributes can not be overridden. For this variable
  you can not override the `for` attribute.

{merge:MERGE}
---------------------------

The `{merge:MERGE}` variable displays a merge field where `MERGE` is the merge tag
for that field (e.g. `{merge:EMAIL}`).

The `{merge:MERGE}` variable accepts `attr:ATTR` parameters as described above. The
attributes than can not be overridden on this variable are `name`, `id` and
`required`.

{error:MERGE}
---------------------------

The `{error:MERGE}` variable displays an error if a field is not filled out
correctly where `MERGE` is the merge tag for the field. If the field is filled
out correctly, nothing is displayed (not even the wrapping elements).

{submit}
---------------------------

The `{submit}` variable displays the submit button for the form.

The `{submit}` variable accepts for following parameters:

- `type` - This determines whether the outputted tag will be an `input[type=submit]`
  or a `button`. Any value other than "button" will display an `input[type=submit]`.
- `value` - The text displayed on the submit button. (Default "Subscribe")
- `attr:ATTR` - As described above. For this variable you can not override the
  `type` attribute.


Conditional Variables
===========================

{error:MERGE}
---------------------------

The `{error:MERGE}` conditional variable can be used to create custom error
messages.

{success}
---------------------------

The `{success}` conditional variable can be used to display a success message if
if the form submission is successful.


Example
===========================

```
{exp:simple_mailchimp
api_key="1234567890abcdef1234567890abcdef-us2"
list_id="1234567890"
error_delimeters='<p class="error">|</p>'}
    {if success}
        <p class="success">Success! Check your email to activate your subscription.</p>
    {if:else}
        <p>
            {label:EMAIL}
            {merge:EMAIL}
            {error:EMAIL}
        </p>
        <p>
            {label:MMERGE1}
            {merge:MMERGE1}
            {if error:MMERGE1}This is a custom error!{/if}
        </p>
        {submit}
    {/if}
{/exp:simple_mailchimp}
```


Changelog
===========================

Version 1.5.3
---------------------------

- Fix errors in PHP 5.2

Version 1.5.2
---------------------------

- Fix regression where subscriptions would fail

Version 1.5.1
---------------------------

- Massive speed increase (~250x faster)
- Update MCAPI

Version 1.5.0
---------------------------

- Remove for="" attr from labels whose input doesn't have an id=""
- Remove id="" attr from radio inputs
- Show warning for unsupported field types
- Add w3c_validate="" option to prevent non-valid (but helpful attributes)
- Fix novalidate="" attr value
- Don't output type="" attr twice for text inputs
- Fix issue where form could POST to the wrong location

Version 1.4.0
---------------------------

- Add support for more field types

Version 1.3.0
---------------------------

- Add double_optin parameter

Version 1.2.1
---------------------------

- Fix errors when non-existant MERGE tags are used

Version 1.2.0
---------------------------

- Add browser_validate parameter to plugin tag
- Add error:MERGE conditionals

Version 1.1.3
---------------------------

- When showing errors, merge fields should retain previously entered content

Version 1.1.2
---------------------------

- Fix bug where form may POST to index.php

Version 1.1.1
---------------------------

- Fix bug where control panel would display white screen of death

Version 1.1.0
---------------------------

- Add email_field parameter to plugin tag
- Add parameters and attributes to single vars
- Fix bug where confirmation email would always sent to site owner
- Fix a bug where form would just redirect to site index and not be processed

Version 1.0.0
---------------------------

- Initial release

    <?php
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
}
// END CLASS
