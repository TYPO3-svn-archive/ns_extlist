                       
Ext.ns('TYPO3');
TYPO3.settings = {"datePickerUSmode":0,"dateFormat":["j-n-Y","G:i j-n-Y"],"dateFormatUS":["n-j-Y","G:i n-j-Y"]};

// class to manipulate TCEFORMS
TYPO3.QUICKEDIT = {

 init: function(){
    //keep it remember temporarily the hided recode item
    var tempHidedItem = '';
    //quick edit event for each and individual row item    
    //remove actoin of help link
    Ext.select('a.quick-edit').on('click', function(e,traget){        
        //get the current node index                
        var rowIdobj = Ext.get(traget.up('tbody').id);                    
        var index = rowIdobj.select('tr').indexOf(traget.up('tr').id);
        //get the columns of one row
        var tds = Ext.get(traget.up('tr').id).select('td').getCount();        
        //hide the current row
        var currentRow = Ext.get(traget.up('tr').id);
            currentRow.applyStyles({ display: 'none' });
        //get the container width
        var tableWidth = rowIdobj.getWidth() - 40;
        
        //get the row item data id, table
        var rowId = Ext.get(traget.up('tr').id).getAttribute('data-id');
        var table = Ext.get(traget.up('tbody').id).getAttribute('data-tablename');
        var vc = Ext.get(traget.up('tbody').id).getAttribute('data-vc');
        
        //first find the quick edit panle is on a specific record table
        if(Ext.get('quick-edit-id') != null){
            Ext.get('quick-edit-id').remove();
            tempHidedItem.show();
        }
        //set the obj of temporarily hided recode item
        tempHidedItem = currentRow;
        
        rowIdobj.createChild({
                            tag: 'tr',
                            id: 'quick-edit-id',
                            class: 'db_list_normal',
                            html: '<td class="quick-edit-wrapper" colspan="'+tds+'">\
                                    <div  id="temp-quick-edit-container"></div>\
                                        <div id="quick-controllers">\
                                            <p><input id="submit-quick-edit" type="submit" name="save-quick" value="Update" />\
                                            <span> or </span>\
                                            <a id="close-quick" href="#">Don\'t Save</a></p>\
                                    </div>\
                                  </td>'
                           }, rowIdobj.select('tr').item(index));
            
        //get a clone of quick edit container
        //var container = Ext.get('quick-edit-container').dom.cloneNode(true);      
        //rowId.insertSibling(container);
        
            // second tabs built from JS
            var tabs2 = new Ext.Panel({
                renderTo: 'temp-quick-edit-container',
                activeTab: 0,
                title: "Quick Edit",
                frame:true,                
                width: tableWidth,
                autoHeight : true,
                plain:true,
                closable: true,
                defaults:{autoScroll: true},
                items:[{
                        //title: 'Content',                        
                        autoLoad: {
                                url: 'ajax.php?ajaxID=tx_nsdynamicc_inplaceediting::quickContent&edit['+table+']['+rowId+']=edit',
                                callback: function() {
                                    TYPO3.QUICKEDIT.convertDateFieldsToDatePicker();
                                    //enable check box value change
                                    TYPO3.QUICKEDIT.changeQuickEditCheckBoxsValues();
                                }
                            },
                        autoHeight: true
                    } 
                ],                
                listeners:{
                    'render': function(){
                        //###########close the quick edit
                        Ext.get('close-quick').on('click', function(e,traget){
                            //get the perant of
                            Ext.get('quick-edit-id').remove();
                            currentRow.show();
                            e.stopEvent();        
                        });
                        
                        ///###########Get the change values form quick edit panel 
                        Ext.get('submit-quick-edit').on('click', function(e,traget){
                        //get the perant of                            
                        e.stopEvent();
                        //
                        var params = new Object();                                                        
                        var getFieldsVals =  Ext.query('.quickedit-field');                        
                        var numberOfFields = getFieldsVals.length;                                            
                                                                    
                         Ext.each(getFieldsVals, function(item){                            
                            // get the input item value and name to the associative array                            
                            params[item.name] = item.value;
                            dataClass = Ext.get(item.id).getAttribute('data-column');                        
                            //change the fields values of inline row
                            if(dataClass != null){
                                TYPO3.QUICKEDIT.changeColoumnValueQuickEditPanle(tempHidedItem,dataClass,item.value);
                            }
                         });
                    
                        /** date timestamp  */
                        //var date = evalFunc.evalObjValue('date','15-8-2010');                        
                         
                        //loading indicator
                        mask = new Ext.LoadMask(Ext.getBody(), {
                             msg: "Updating..."
                         });
                        //show the mask				
                        mask.show();                                                  
                          //save quick edit changes via Aajx request
                          Ext.Ajax.request({
                            url: 'ajax.php?ajaxID=tx_nsdynamicc_inplaceediting::quickEditSubmit&edit['+table+']['+rowId+']=edit&vC='+vc,
                            params: params,
                            timeout: 3000,
                            method: 'POST',
                            success: function(xhr) {                                
                              var obj = xhr.responseText;
                              //if successfully the ajax request just hide the mask
                              if(obj){
                                //delete quick edit panle 
                                Ext.get('quick-edit-id').remove();
                                //show current row with update details 
                                currentRow.show();
                                //hide the loading maks 
                                mask.hide();
                              }
                            }
                          });
                        });
                    }
                }
            });
    });
  },
    ///ready the datapickar
  convertDateFieldsToDatePicker: function() {
        
		var dateFields = Ext.select("*[id^=tceforms-datefield-], *[id^=tceforms-datetimefield-]");
		dateFields.each(function(element) {
			var index = element.dom.id.match(/tceforms-datefield-/) ? 0 : 1;
			var format = TYPO3.settings.datePickerUSmode ? TYPO3.settings.dateFormatUS : TYPO3.settings.dateFormat;

			var datepicker = Ext.get('picker-' + element.dom.id);

			var menu = new Ext.menu.DateMenu({
				id:			'p' + element.dom.id,
				format:		format[index],
				value:		Date.parseDate(element.dom.value, format[index]),
				handler: 	function(picker, date){
					var relElement = Ext.getDom(picker.ownerCt.id.substring(1));          
					relElement.value = date.format(format[index]);          
					if (Ext.isFunction(relElement.onchange)) {
						relElement.onchange.call(relElement);
					}
				},
				listeners:	{
					beforeshow:	function(obj) {
						var relElement = Ext.getDom(obj.picker.ownerCt.id.substring(1));
						if (relElement.value) {
							obj.picker.setValue(Date.parseDate(relElement.value, format[index]));
						}            
					}
				}
			});

			datepicker.on('click', function(){
				menu.show(datepicker);
			});
		});
	},
  
  //change check box values on quick edit panel
  changeQuickEditCheckBoxsValues: function(){                
    //change check box values
    Ext.select('.form-container .checkbox').on('click', function(e,traget){                
        if(traget.checked == true){
            Ext.get(traget.id).set({value: 1});                    
        }
        else{
            Ext.get(traget.id).set({value: 0});
        }
    });
  },
  
  //chnage the fields value presentaion of the row item
  changeColoumnValueQuickEditPanle: function(tempHidedItem,dataClass, value){
       Ext.select('#'+tempHidedItem.id+ ' .'+dataClass).update(value);
  }
}

Ext.onReady(TYPO3.QUICKEDIT.init, TYPO3.QUICKEDIT);
