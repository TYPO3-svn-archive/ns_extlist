/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Default Link Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id: default-link.js 7300 2010-04-12 05:49:17Z stan $
 */
HTMLArea.DefaultLink = HTMLArea.Plugin.extend({
	constructor: function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.baseURL = this.editorConfiguration.baseURL;
		this.pageTSConfiguration = this.editorConfiguration.buttons.link;
		this.stripBaseUrl = this.pageTSConfiguration && this.pageTSConfiguration.stripBaseUrl && this.pageTSConfiguration.stripBaseUrl;
		this.showTarget = !(this.pageTSConfiguration && this.pageTSConfiguration.targetSelector && this.pageTSConfiguration.targetSelector.disabled);
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.0',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		var buttonList = this.buttonList, buttonId;
		for (var i = 0; i < buttonList.length; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId.toLowerCase()),
				iconCls		: 'htmlarea-action-' + button[4],
				action		: 'onButtonPress',
				hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
				context		: button[1],
				selection	: button[2],
				dialog		: button[3]
			};
			this.registerButton(buttonConfiguration);
		}
		return true;
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['CreateLink', 'a,img', false, true, 'link-edit'],
		['UnLink', 'a', false, false, 'unlink']
	],
	/*
	 * Sets of default configuration values for dialogue form fields
	 */
	configDefaults: {
		combo: {
			editable: true,
			typeAhead: true,
			triggerAction: 'all',
			forceSelection: true,
			mode: 'local',
			valueField: 'value',
			displayField: 'text',
			helpIcon: true,
			tpl: '<tpl for="."><div ext:qtip="{value}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
		}
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function(editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.editor.focus();
		this.link = this.editor.getParentElement();
		var el = HTMLArea.getElementObject(this.link, 'a');
		if (el && /^a$/i.test(el.nodeName)) {
			this.link = el;
		}
		if (!this.link || !/^a$/i.test(this.link.nodeName)) {
			this.link = null;
		}
		switch (buttonId) {
			case 'UnLink':
				this.unLink();
				break;
			case 'CreateLink':
				if (!this.link) {
					var selection = this.editor._getSelection();
					if (this.editor._selectionEmpty(selection)) {
						Ext.MessageBox.alert('', this.localize('Select some text'));
						break;
					}
					this.parameters = {
						href:	'http://',
						title:	'',
						target:	''
					};
				} else {
					this.parameters = {
						href:	(Ext.isIE && this.stripBaseUrl) ? this.stripBaseURL(this.link.href) : this.link.getAttribute('href'),
						title:	this.link.title,
						target:	this.link.target
					};
				}
					// Open dialogue window
				this.openDialogue(
					buttonId,
					'Insert/Modify Link',
					this.getWindowDimensions(
						{
							width: 470,
							height:150
						},
						buttonId
					)
				);
				break;
		}
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	integer		dimensions: the opening width of the window
	 *
	 * @return	void
	 */
	openDialogue: function (buttonId, title, dimensions) {
		this.dialog = new Ext.Window({
			title: this.localize(title),
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
				// As of ExtJS 3.1, JS error with IE when the window is resizable
			resizable: !Ext.isIE,
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				afterrender: {
					fn: this.onAfterRender,
					scope: this
				},
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: [{
					xtype: 'fieldset',
					defaultType: 'textfield',
					labelWidth: 100,
					defaults: {
						helpIcon: true,
						width: 250,
						labelSeparator: ''
					},
					items: [{
							itemId: 'href',
							name: 'href',
							fieldLabel: this.localize('URL:'),
							value: this.parameters.href,
							helpTitle: this.localize('link_href_tooltip')
						},{
							itemId: 'title',
							name: 'title',
							fieldLabel: this.localize('Title (tooltip):'),
							value: this.parameters.title,
							helpTitle: this.localize('link_title_tooltip')
						}, Ext.apply({
							xtype: 'combo',
							fieldLabel: this.localize('Target:'),
							itemId: 'target',
							helpTitle: this.localize('link_target_tooltip'),
							store: new Ext.data.ArrayStore({
								autoDestroy:  true,
								fields: [ { name: 'text'}, { name: 'value'}],
								data: [
									[this.localize('target_none'), ''],
									[this.localize('target_blank'), '_blank'],
									[this.localize('target_self'), '_self'],
									[this.localize('target_top'), '_top'],
									[this.localize('target_other'), '_other']
								]
							}),
							listeners: {
								select: {
									fn: this.onTargetSelect
								}
							},
							hidden: !this.showTarget
							}, this.configDefaults['combo'])
						,{
							itemId: 'frame',
							name: 'frame',
							fieldLabel: this.localize('frame'),
							helpTitle: this.localize('frame_help'),
							hideLabel: true,
							hidden: true
						}
					]
				}
			],
			buttons: [
				this.buildButtonConfig('OK', this.onOK),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Handler invoked after the dialogue window is rendered
	 * If the current target is not in the available options, show frame field
	 */
	onAfterRender: function (dialog) {
		var targetCombo = dialog.find('itemId', 'target')[0];
		if (!targetCombo.hidden && this.parameters.target) {
			var frameField = dialog.find('itemId', 'frame')[0];
			var index = targetCombo.getStore().find('value', this.parameters.target);
			if (index == -1) {
					// The target is a specific frame name
				targetCombo.setValue('_other');
				frameField.setValue(this.parameters.target);
				frameField.show();
				frameField.label.show();
			} else {
				targetCombo.setValue(this.parameters.target);
			}
		}
	},
	/*
	 * Handler invoked when a target is selected
	 */
	onTargetSelect: function (combo, record) {
		var frameField = combo.ownerCt.getComponent('frame');
		if (record.get('value') == '_other') {
			frameField.show();
			frameField.label.show();
			frameField.focus();
		} else if (!frameField.hidden) {
			frameField.hide();
			frameField.label.hide();
		}
	},
	/*
	 * Handler invoked when the OK button is clicked
	 */
	onOK: function () {
		var hrefField = this.dialog.find('itemId', 'href')[0];
		var href = hrefField.getValue().trim();
		if (href) {
			var title = this.dialog.find('itemId', 'title')[0].getValue();
			var target = this.dialog.find('itemId', 'target')[0].getValue();
			if (target == '_other') {
				target = this.dialog.find('itemId', 'frame')[0].getValue().trim();
			}
			this.createLink(href, title, target);
			this.close();
		} else {
			Ext.MessageBox.alert('', this.localize('link_url_required'), function () { hrefField.focus(); });
		}
		return false;
	},
	/*
	 * Create the link
	 *
	 * @param	string		href: the value of href attribute
	 * @param	string		title: the value of title attribute
	 * @param	string		target: the value of target attribute
	 *
	 * @return	void
	 */
	createLink: function (href, title, target) {
		var a = this.link;
		if (!a) {
			this.editor.focus();
			this.restoreSelection();
			this.editor.document.execCommand('CreateLink', false, href);
			a = this.editor.getParentElement();
			if (!Ext.isIE && !/^a$/i.test(a.nodeName)) {
				var range = this.editor._createRange(this.editor._getSelection());
				if (range.startContainer.nodeType != 3) {
					a = range.startContainer.childNodes[range.startOffset];
				} else {
					a = range.startContainer.nextSibling;
				}
				this.editor.selectNode(a);
			}
			var el = HTMLArea.getElementObject(a, 'a');
			if (el != null && /^a$/i.test(el.nodeName)) {
				a = el;
			}
		} else {
			a.href = href;
		}
		if (a && /^a$/i.test(a.nodeName)) {
			a.title = title;
			a.target = target;
			if (Ext.isOpera) {
				this.editor.selectNodeContents(a, false);
			} else {
				this.editor.selectNodeContents(a);
			}
		}
	},
	/*
	 * Unlink the selection
	 */
	unLink: function () {
		this.editor.focus();
		this.restoreSelection();
		if (this.link) {
			this.editor.selectNode(this.link);
		}
		this.editor.document.execCommand('Unlink', false, '');
	},
	/*
	 * IE makes relative links absolute. This function reverts this conversion.
	 *
	 * @param	string		url: the url
	 *
	 * @return	string		the url stripped out of the baseurl
	 */
	stripBaseURL: function (url) {
		var baseurl = this.baseURL;
			// strip to last directory in case baseurl points to a file
		baseurl = baseurl.replace(/[^\/]+$/, '');
		var basere = new RegExp(baseurl);
		url = url.replace(basere, '');
			// strip host-part of URL which is added by MSIE to links relative to server root
		baseurl = baseurl.replace(/^(https?:\/\/[^\/]+)(.*)$/, "$1");
		basere = new RegExp(baseurl);
		return url.replace(basere, '');
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable() && button.itemId === 'CreateLink') {
			button.setDisabled(selectionEmpty && !button.isInContext(mode, selectionEmpty, ancestors));
		}
	}
});
