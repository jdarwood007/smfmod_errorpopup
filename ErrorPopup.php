<?php

/**
 * The class for Error Popup.  We don't actually need a class, but it prevents clashes
 * @package ErrorPopup
 * @author SleePy <sleepy @ simplemachines (dot) org>
 * @copyright 2022
 * @license 3-Clause BSD https://opensource.org/licenses/BSD-3-Clause
 * @version 1.1
 */
class ErrorPopup
{
	/*
	 * Inject into the menu system current action.
	 * Nothing is returned, but we do inject some javascript and css.
	 * Thise will clone the Admin Menu > Error Log menu into the top menu (User menu)
	 *
	 * @CalledBy: setupMenuContext - integrate_current_action
	*/
	public static function hook_current_action(): void
	{
		global $context, $scripturl, $txt;
		static $calledOnce = false;

		if ($calledOnce)
			return;
		$calledOnce = true;

		// Don't bother with non admins.
		if (empty($context['user']['is_admin']))
			return;

		// We are loaded via SSI, we can fake it.
		if (SMF == 'SSI')
			addInlineJavaScript('
			var errorLI = \'<li class=""><a href="' . $scripturl . '?action=admin;area=logs;sa=errorlog;desc">' . $txt['errorlog'] . ' <span class="amt">' . ($context['num_errors'] ?? 0) . '</span></a></li>\';');
		else
			addInlineJavaScript('
			var errorLI = $("li.button_admin ul").find(\'a[href*="errorlog"]\').parent().prop("outerHTML");', true);

			addInlineJavaScript('
			errorLI += \'<div id="error_menu" class="top_menu scrollable" style="width: 90vw; max-width: 1200px;"></div>\';
			$("ul#top_info").append(errorLI);
			$("ul#top_info").find(\'a[href*="errorlog"]\').attr("id", "error_menu_top").wrapInner("<span class=\"textmenu\"></span>").prepend("<span class=\"main_icons logs\"></span>");
			user_menus.add("error", "' . $scripturl . '?action=admin;area=logs;sa=errorlog");
			$("a#error_menu_top span.amt").detach().appendTo("a#error_menu_top");

			function tryUpdateErrorCounter(xhr) {
				var newErrorCount = xhr.getResponseHeader("x-smf-errorlogcount");

				if (parseInt(newErrorCount))
				{
					$("ul#top_info a#error_menu_top .amt").html(parseInt(newErrorCount));
				}
			}
			', true);

		// Fixes a minor bug where the content isn't sized right.
		addInlineCss('
			div#error_menu .half_content { width: 49%;}
			#error_menu_top .main_icons {display: none;}
			@media (max-width: 855px) {
				#error_menu_top .main_icons {display: inline-block;}
				#error_menu_top .textmenu {display: none;}
			}
		');

		// Fix the admin login prompt to work.
		addInlineJavaScript('
			$("div#error_menu").on("submit", "#frmLogin", function(e) {
				e.preventDefault();
				e.stopPropagation();

				form = $("div#error_menu #frmLogin");
				$.ajax({
					url: form.prop("action") + ";ajax",
					method: "POST",
					headers: {
						"X-SMF-AJAX": 1
					},
					xhrFields: {
						withCredentials: typeof allow_xhjr_credentials !== "undefined" ? allow_xhjr_credentials : false
					},
					data: form.serialize(),
					success: function(data, status, xhr) {
						if (typeof xhr != "undefined")
							tryUpdateErrorCounter(xhr);

						if (data.indexOf("<bo" + "dy") > -1) {
							document.open();
							document.write(data);
							document.close();
						}
						else if (data.indexOf("<form") > -1) {
							form.html($(data).html());
							$("div#error_menu").customScrollbar().resize()
						}
						else
							form.parent().html($(data).find(".roundframe").html());
					},
					error: function(xhr) {
						var data = xhr.responseText;
						if (data.indexOf("<bo" + "dy") > -1) {
							document.open();
							document.write(data);
							document.close();
						}
						else
							form.parent().html($(data).filter("#fatal_error").html());

						$("div#error_menu").customScrollbar().resize()
					}
				});

				return false;
			});', true);

		// Add the logic to make hyperlinks work when we are in the popup.
		addInlineJavaScript('
			$("div#error_menu").on("click", "div.information a, div.pagelinks a.nav_page, div.half_content > a[href*=\'errorlog\']", function(e) {
				e.preventDefault();
				e.stopPropagation();

				currentLink = e.currentTarget.href;
				contentBox = $("div#error_menu .overview");

				$.ajax({
					url: currentLink + ";ajax",
					method: "GET",
					headers: {
						"X-SMF-AJAX": 1
					},
					xhrFields: {
						withCredentials: typeof allow_xhjr_credentials !== "undefined" ? allow_xhjr_credentials : false
					},
					success: function(data, status, xhr) {
						if (typeof xhr != "undefined")
							tryUpdateErrorCounter(xhr);

						if (data.indexOf("<bo" + "dy") > -1) {
							document.open();
							document.write(data);
							document.close();
						}
						else
							contentBox.html(data);

						$("div#error_menu").customScrollbar().resize()
					},
					error: function(xhr) {
						var data = xhr.responseText;
						if (data.indexOf("<bo" + "dy") > -1) {
							document.open();
							document.write(data);
							document.close();
						}
						else
							contentBox.html($(data).filter("#fatal_error").html());

						$("div#error_menu").customScrollbar().resize()
					}
				});
			});', true);

		// Fix the admin login prompt to work.
		addInlineJavaScript('
			$("div#error_menu").on("click", "form[action*=\'errorlog\'] input:submit", function(e) {
				e.preventDefault();
				e.stopPropagation();

				button = $(e.currentTarget);
				form = $("div#error_menu form[action*=\'errorlog\']");
				formData = form.serialize();
				if (button.attr("name") !== undefined) {
					formData += "&"+button.attr("name")+"=";
				}

				$.ajax({
					url: form.prop("action") + ";ajax",
					method: "POST",
					headers: {
						"X-SMF-AJAX": 1
					},
					xhrFields: {
						withCredentials: typeof allow_xhjr_credentials !== "undefined" ? allow_xhjr_credentials : false
					},
					data: formData,
					success: function(data, status, xhr) {
						if (typeof xhr != "undefined")
							tryUpdateErrorCounter(xhr);

						if (data.indexOf("<bo" + "dy") > -1) {
							document.open();
							document.write(data);
							document.close();
						}
						else if (data.indexOf("<form") > -1) {
							form.html($(data).html());
							$("div#error_menu").customScrollbar().resize()
						}
						else
							form.parent().html($(data).html());

						$("div#error_menu").customScrollbar().resize()
					},
					error: function(xhr) {
						var data = xhr.responseText;
						if (data.indexOf("<bo" + "dy") > -1) {
							document.open();
							document.write(data);
							document.close();
						}
						else
							form.parent().html($(data).filter("#fatal_error").html());

						$("div#error_menu").customScrollbar().resize()
					}
				});

				return false;
			});', true);

		// Fix up the select code links.
		addInlineJavaScript('
			$("div#error_menu").on("click", ".smf_select_text", function(e) {
				e.preventDefault();

				// Do you want to target yourself?
				var actOnElement = $(this).attr("data-actonelement");

				return typeof actOnElement !== "undefined" ? smfSelectText(actOnElement, true) : smfSelectText(this);
			});', true);
	}

	/*
	 * When we are on the logs sub action, we allow a ajax action to strip html.
	 *
	 * @CalledBy: AdminLogs - integrate_manage_logs
	*/
	public static function hook_manage_logs(&$log_functions): void
	{
		global $context, $db_show_debug, $smcFunc;

		// Not a AJAX request.
		if (!isset($_REQUEST['ajax']))
			return;

		// Strip away layers and remove debugger.
		$context['template_layers'] = array();
		$db_show_debug = false;

		// Sneak a header in here that we can use to update the counter.
		if (!headers_sent())
		{
			$result = $smcFunc['db_query']('', '
				SELECT COUNT(*)
				FROM {db_prefix}log_errors',
				[]
			);
			list ($num_errors) = $smcFunc['db_fetch_row']($result);
			$smcFunc['db_free_result']($result);
		
			header('x-smf-errorlogcount: ' . $num_errors);
		}
	}

	/*
	 * When we are on the logs sub action, we allow a ajax action to strip html.
	 *
	 * @CalledBy: AdminLogs - integrate_manage_logs
	*/
	public static function hook_validateSession(&$types): void
	{
		global $context, $db_show_debug;

		// Not a AJAX request.
		if (
			!isset($_REQUEST['ajax'], $_REQUEST['action'], $_REQUEST['area'])
			|| $_REQUEST['action'] != 'admin'
			|| $_REQUEST['area'] != 'logs'
		)
			return;

		// Strip away layers and remove debugger.
		$context['template_layers'] = array();
		$db_show_debug = false;
	}

	/*
	 * When we are on the logs sub action, we allow a ajax action to strip html.
	 *
	 * @CalledBy: redirectexit - integrate_redirect
	*/
	public static function hook_redirect(&$setLocation, &$refresh, &$permanent): void
	{
		// We are on a error log action such as delete.
		if (
			isset($_REQUEST['action'], $_REQUEST['area'], $_REQUEST['sa'], $_REQUEST['ajax'])
			&& $_REQUEST['action'] == 'admin'
			&& $_REQUEST['area'] == 'logs'
			&& (
				isset($_POST['delall'])
				|| isset($_POST['delete'])
			) 
		)
			$setLocation .= ';ajax';
	}
}

/*
 * This is a special loader function designed to make it easy to have this function without hooks for mod testing.
 * You need to call this file in something very early in SMF, before reloadSettings.php is called
*/
if (!defined('SMF_INTEGRATION_SETTINGS'))
	define('SMF_INTEGRATION_SETTINGS', json_encode([
		'integrate_current_action' => 'ErrorPopup::hook_current_action',
		'integrate_manage_logs' => 'ErrorPopup::hook_manage_logs',
		'integrate_validateSession' => 'ErrorPopup::hook_validateSession',
		'integrate_redirect' => 'ErrorPopup::hook_redirect',
	]));