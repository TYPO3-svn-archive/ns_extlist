/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * TYPO3 Color Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id: typo3color.js 7328 2010-04-13 05:19:47Z stan $
 */
HTMLArea.TYPO3Color = HTMLArea.Plugin.extend({
	constructor: function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		this.colorsConfiguration = this.editorConfiguration.colors;
		this.disableColorPicker = this.editorConfiguration.disableColorPicker;
			// Coloring will use the style attribute
		if (this.editor.plugins.TextStyle && this.editor.plugins.TextStyle.instance) {
			this.editor.plugins.TextStyle.instance.addAllowedAttribute('style');
			this.allowedAttributes = this.editor.plugins.TextStyle.instance.allowedAttributes;
		}
		if (this.editor.plugins.InlineElements && this.editor.plugins.InlineElements.instance) {
			this.editor.plugins.InlineElements.instance.addAllowedAttribute('style');
			if (!this.allowedAllowedAttributes) {
				this.allowedAttributes = this.editor.plugins.InlineElements.instance.allowedAttributes;
			}
		}
		if (this.editor.plugins.BlockElements && this.editor.plugins.BlockElements.instance) {
			this.editor.plugins.BlockElements.instance.addAllowedAttribute('style');
		}
		if (!this.allowedAttributes) {
			this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'style');
			if (Ext.isIE) {
				this.allowedAttributes.push('className');
			}
		}
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '4.0',
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
				tooltip		: this.localize(buttonId),
				iconCls		: 'htmlarea-action-' + button[2],
				action		: 'onButtonPress',
				hotKey		: (this.buttonsConfiguration[button[1]] ? this.buttonsConfiguration[button[1]].hotKey : null),
				dialog		: true
			};
			this.registerButton(buttonConfiguration);
		}
		return true;
	 },
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['ForeColor', 'textcolor', 'color-foreground'],
		['HiliteColor', 'bgcolor', 'color-background']
	],
	/*
	 * Conversion object: button name to corresponding style property name
	 */
	styleProperty: {
		ForeColor	: 'color',
		HiliteColor	: 'backgroundColor'
	},
	colors: [
		'000000', '222222', '444444', '666666', '999999', 'BBBBBB', 'DDDDDD', 'FFFFFF',
		'660000', '663300', '996633', '003300', '003399', '000066', '330066', '660066',
		'990000', '993300', 'CC9900', '006600', '0033FF', '000099', '660099', '990066',
		'CC0000', 'CC3300', 'FFCC00', '009900', '0066FF', '0000CC', '663399', 'CC0099',
		'FF0000', 'FF3300', 'FFFF00', '00CC00', '0099FF', '0000FF', '9900CC', 'FF0099',
		'CC3333', 'FF6600', 'FFFF33', '00FF00', '00CCFF', '3366FF', '9933FF', 'FF00FF',
		'FF6666', 'FF6633', 'FFFF66', '66FF66', '00FFFF', '3399FF', '9966FF', 'FF66FF',
		'FF9999', 'FF9966', 'FFFF99', '99FF99', '99FFFF', '66CCFF', '9999FF', 'FF99FF',
		'FFCCCC', 'FFCC99', 'FFFFCC', 'CCFFCC', 'CCFFFF', '99CCFF', 'CCCCFF', 'FFCCFF'
	],
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		var element = this.editor.getParentElement();
		this.openDialogue(
			buttonId + '_title',
			{
				element: element,
				buttonId: buttonId
			},
			this.getWindowDimensions({ width: 350}, buttonId),
			this.buildItemsConfig(element, buttonId),
			this.setColor
		);
	},
	/*
	 * Build the window items config
	 */
	buildItemsConfig: function (element, buttonId) {
		var itemsConfig = [];
		var paletteItems = [];
			// Standard colors palette (boxed)
		if (!this.disableColorPicker) {
			paletteItems.push({
				xtype: 'container',
				items: {
					xtype: 'colorpalette',
					itemId: 'color-palette',
					colors: this.colors,
					cls: 'color-palette',
					value: (element && element.style[this.styleProperty[buttonId]]) ? HTMLArea.util.Color.colorToHex(element.style[this.styleProperty[buttonId]]).substr(1, 6) : '',
					allowReselect: true,
					listeners: {
						select: {
							fn: this.onSelect,
							scope: this
						}
					}
				}
			});
		}
			// Custom colors palette (boxed)
		if (this.colorsConfiguration) {
			paletteItems.push({
				xtype: 'container',
				items: {
					xtype: 'colorpalette',
					itemId: 'custom-colors',
					cls: 'htmlarea-custom-colors',
					colors: this.colorsConfiguration,
					value: (element && element.style[this.styleProperty[buttonId]]) ? HTMLArea.util.Color.colorToHex(element.style[this.styleProperty[buttonId]]).substr(1, 6) : '',
					tpl: new Ext.XTemplate(
						'<tpl for="."><a href="#" class="color-{1}" hidefocus="on"><em><span style="background:#{1}" unselectable="on">&#160;</span></em><span unselectable="on">{0}<span></a></tpl>'
					),
					allowReselect: true,
					listeners: {
						select: {
							fn: this.onSelect,
							scope: this
						}
					}
				}
			});
		}
		itemsConfig.push({
			xtype: 'container',
			layout: 'hbox',
			items: paletteItems
		});
		itemsConfig.push({
			xtype: 'displayfield',
			itemId: 'show-color',
			cls: 'show-color',
			width: 60,
			height: 22,
			helpTitle: this.localize(buttonId)
		});
		itemsConfig.push({
			itemId: 'color',
			cls: 'color',
			width: 60,
			minValue: 0,
			value: (element && element.style[this.styleProperty[buttonId]]) ? HTMLArea.util.Color.colorToHex(element.style[this.styleProperty[buttonId]]).substr(1, 6) : '',
			enableKeyEvents: true,
			fieldLabel: this.localize(buttonId),
			helpTitle: this.localize(buttonId),
			listeners: {
				change: {
					fn: this.onChange,
					scope: this
				},
				afterrender: {
					fn: this.onAfterRender,
					scope: this
				}
			}
		});
	 	return {
			xtype: 'fieldset',
			title: this.localize('color_title'),
			defaultType: 'textfield',
			labelWidth: 175,
			defaults: {
				helpIcon: false
			},
			items: itemsConfig
		};
	},
	/*
	 * On select handler: set the value of the color field, display the new color and update the other palette
	 */
	onSelect: function (palette, color) {
		this.dialog.find('itemId', 'color')[0].setValue(color);
		this.showColor(color);
		if (palette.getItemId() == 'color-palette') {
			var customPalette = this.dialog.find('itemId', 'custom-colors')[0];
			if (customPalette) {
				customPalette.deSelect();
			}
		} else {
			var standardPalette = this.dialog.find('itemId', 'color-palette')[0];
			if (standardPalette) {
				standardPalette.deSelect();
			}
		}
	},
	/*
	 * Display the selected color
	 */
	showColor: function (color) {
		if (color) {
			this.dialog.find('itemId', 'show-color')[0].el.setStyle('backgroundColor', '#' + color);
		}
	},
	/*
	 * On change handler: display the new color and select it in the palettes, if it exists
	 */
	onChange: function (field, value) {
		if (value) {
			var color = value.toUpperCase();
			this.showColor(color);
			var standardPalette = this.dialog.find('itemId', 'color-palette')[0];
			if (standardPalette) {
				standardPalette.select(color);
			}
			var customPalette = this.dialog.find('itemId', 'custom-colors')[0];
			if (customPalette) {
				customPalette.select(color);
			}
		}
	},
	/*
	 * On after render handler: display the color
	 */
	onAfterRender: function (field) {
		if (!Ext.isEmpty(field.getValue())) {
			this.showColor(field.getValue());
		}
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		title: the window title
	 * @param	object		arguments: some arguments for the handler
	 * @param	integer		dimensions: the opening width of the window
	 * @param	object		tabItems: the configuration of the tabbed panel
	 * @param	function	handler: handler when the OK button if clicked
	 *
	 * @return	void
	 */
	openDialogue: function (title, arguments, dimensions, items, handler) {
		if (this.dialog) {
			this.dialog.close();
		}
		this.dialog = new Ext.Window({
			title: this.localize(title),
			arguments: arguments,
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
				// As of ExtJS 3.1, JS error with IE when the window is resizable
			resizable: !Ext.isIE,
			iconCls: this.getButton(arguments.buttonId).iconCls,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
				xtype: 'container',
				layout: 'form',
				defaults: {
					labelWidth: 150
				},
				items: items
			},
			buttons: [
				this.buildButtonConfig('Cancel', this.onCancel),
				this.buildButtonConfig('OK', handler)
			]
		});
		this.show();
	},
	/*
	 * Set the color and close the dialogue
	 */
	setColor: function(button, event) {
		this.restoreSelection();
		var buttonId = this.dialog.arguments.buttonId;
		var color = this.dialog.find('itemId', 'color')[0].getValue();
		if (color) {
			color = '#' + color;
		}
		this.editor.focus();
		var 	element,
			fullNodeSelected = false;
		var selection = this.editor._getSelection();
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement(selection, range);
		var selectionEmpty = this.editor._selectionEmpty(selection);
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
		if (!selectionEmpty) {
			var ancestors = this.editor.getAllAncestors();
			var fullySelectedNode = this.editor.getFullySelectedNode(selection, range, ancestors);
			if (fullySelectedNode) {
				fullNodeSelected = true;
				parent = fullySelectedNode;
			}
		}
		if (selectionEmpty || fullNodeSelected) {
			element = parent;
				// Set the color in the style attribute
			element.style[this.styleProperty[buttonId]] = color;
				// Remove the span tag if it has no more attribute
			if ((element.nodeName.toLowerCase() === 'span') && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
				this.editor.removeMarkup(element);
			}
		} else if (statusBarSelection) {
			var element = statusBarSelection;
				// Set the color in the style attribute
			element.style[this.styleProperty[buttonId]] = color;
				// Remove the span tag if it has no more attribute
			if ((element.nodeName.toLowerCase() === 'span') && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
				this.editor.removeMarkup(element);
			}
		} else if (color && this.editor.endPointsInSameBlock()) {
			var element = this.editor._doc.createElement('span');
				// Set the color in the style attribute
			element.style[this.styleProperty[buttonId]] = color;
			this.editor.wrapWithInlineElement(element, selection, range);
		}
		if (!Ext.isIE) {
			range.detach();
		}
		this.close();
		event.stopEvent();
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null,
				parentElement = statusBarSelection ? statusBarSelection : this.editor.getParentElement(),
				disabled = !endPointsInSameBlock || (selectionEmpty && /^body$/i.test(parentElement.nodeName));
			button.setInactive(!parentElement.style[this.styleProperty[button.itemId]]);
			button.setDisabled(disabled);
		}
	}
});
