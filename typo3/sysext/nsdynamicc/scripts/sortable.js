Ext.namespace('Ext.ux');

Ext.ux.Sortable = function(obj){
	
	var config = Ext.apply({
		container : document.body,
		className : null,
		tagName : 'li',
		handles : false,
		contextMenu : false,
		dragGroups : [
						'default'
					 ],	
		autoEnable : true,
		horizontal : false,
		queryDepth : 5 //this is up to how many parent nodes the on drag command will chain up to grab a dom node
	},obj);
	
	this.ddGroups = {};
	
	Ext.apply(this,config);
	
	this._buildQueryString();
	
	this.addEvents (
		'serialise',
		'serialize',
		'enable',
		'disable',
		'enableElement',
		'disableElement',
		'initSortable',
		'afterRepair',
		'endDrag',
		'notifyEnter',
		'containerOver',
		'notifyOver',
		'nodeOver',
		'notifyDrop'
	);
		
	this._createDragDrop();
	
	if(this.dragGroups.length > 1){
		this.addToDDGroup(this.dragGroups);
	} else {
		this.ddGroups[this.dragGroups]=true;
	}
	
	//automatically start the DD
	if(this.autoEnable){
		this.enable();
	}	
	
	this.serialize = this.serialise;
	
}

Ext.extend(Ext.ux.Sortable,Ext.util.Observable, {
	/**
	 * Function creates the queryString for use in all functions
	 * @private
	 */
	_buildQueryString : function(){
		this.queryString = '';		
		
		if(this.tagName){
			this.queryString += this.tagName.toLowerCase();			
		}		
		if(this.className){
			this.queryString += '.'+this.className;			
		}
	},
	/**
	 * creates the DragZone and DropTarget
	 * @private
	 */
	_createDragDrop : function(){		
		  this.dragZone = new Ext.dd.SortableDragZone(this.container, {parentClass : this, ddGroup : this.dragGroups[0], scroll:false, containerScroll:true, queryString : this.queryString, handles : this.handles, queryDepth : this.queryDepth});
	    this.dropZone = new Ext.dd.SortableDropZone(this.container, {parentClass : this, ddGroup : this.dragGroups[0], queryString : this.queryString, handles : this.handles, horizontal : this.horizontal});			
	},
	/**
	 * Function gets the items in the list area
	 * @public
	 * @param {Boolean} flag Switch flag to fire event or not
	 * @returns {Array} An array ob DOM references to the nodes contained in the sortable list
	 */	
	serialise : function(flag){
		if(flag || flag == undefined){
			this.fireEvent('serialise', this);
			this.fireEvent('serialize', this);
		}
		//alert(this.queryString+'-'+this.container);
		return Ext.query(this.queryString,this.container);
	},
	/**
	 * Function enables DD on the container element
	 * is a long function to stop evaluation inside loops
	 * @public
	 */
	enable : function(){
		this.drags = this.serialise(false);
		
		var i = this.drags.length-1;
		
		if(this.handles && this.contextMenu){
			
			while(i >= 0){
			
	           Ext.dd.Registry.register(this.drags[i], {
	                   isHandle:false,
	                   handles : [
	                       'handle_' + this.drags[i].id
	                   ],
						ddGroups : this.ddGroups
	               }
	           );
						 //alert(this.drags[i].id);
	           Ext.fly('handle_' + this.drags[i].id).on('contextmenu', this.contextMenu, this, {preventDefault: true});
	       	   --i;
		   }
		} else if (this.handles) {
			while(i >= 0){				
				Ext.dd.Registry.register(this.drags[i], {
		                   isHandle:false,
		                   handles : [
		                       'handle_' + this.drags[i].id
		                   ],
							ddGroups : this.ddGroups    
		               }
		           ); 
				--i;
			}
		} else if(this.contextMenu){
			while(i >= 0){ 
			 	Ext.dd.Registry.register(
					this.drags[i],
					{	
						ddGroups : this.ddGroups
					}	
				);
	         	Ext.fly(this.drags[i].id).on('contextmenu', this.contextMenu, this, {preventDefault: true});
				--i;
			}
		} else {
			while(i >= 0){
				Ext.dd.Registry.register(
					this.drags[i],
					{
						ddGroups : this.ddGroups
					}	
				);
				--i;
			}	
		}
		this.dropZone.unlock();
		this.dragZone.unlock();
		this.fireEvent('enable', this);
	},
	
	/**
	 * Disable all DD and remove contextMenu listeners
	 * @public
	 */
	disable : function(){
		this.drags = this.serialise(false);
		var i = this.drags.length-1;
		if(this.contextMenu){
			while(i >= 0){
		    	Ext.dd.Registry.unregister(this.drags[i]);
		        Ext.fly('handle_' + this.drags[i].id).un('contextmenu', this.contextMenu);
				--i;
		    }
		} else {
			while(i >= 0){
				 Ext.dd.Registry.unregister(this.drags[i]);
				--i;
			}
		} 
		this.dropZone.lock();
		this.dragZone.lock();
		this.fireEvent('disable', this);
	},
	/**
	 * Function enables a single Elements DD within the container
	 * @public
	 * @param {String} id The Id of the element you want to add to the DD list 
	 */
	enableElement :function(id){
		if(this.handles && this.contextMenu){
	
           Ext.dd.Registry.register(id, {
                   isHandle:false,
                   handles : [
                       'handle_' + id
                   ],
    				ddGroups : this.ddGroups
               }
           );
           Ext.fly('handle_' + id).on('contextmenu', this.contextMenu, this, {preventDefault: true});
		      
		} else if (this.handles) {
			Ext.dd.Registry.register(id, {
                   isHandle:false,
                   handles : [
                       'handle_' + id
                   ],
   				   ddGroups : this.ddGroups    
               }
           );
		} else if(this.contextMenu){
			 Ext.dd.Registry.register(id,{
   				ddGroup : this.ddGroups
			});
	         Ext.fly('handle_' + id).un('contextmenu', this.contextMenu);
		} else {
			 Ext.dd.Registry.register(id,{
   				ddGroups : this.ddGroups
			});
		}
		this.fireEvent('enableElement', this);
	},
	/**
	 * Function disables a single Elements DD within the container
	 * @public
	 * @param {String} id The Id of the element you want to disable in the list 
	 */
	disableElement : function(id){
		Ext.dd.Registry.unregister(id);
        if(this.contextMenu){
        	Ext.fly('handle_' + id).un('contextmenu', this.contextMenu);
	 	}
		this.fireEvent('disableElement', this);
	},
	/**
	 * Function switches DD Group from the current one
	 * @public
	 * @param {String/Array} The DD Group(s) you want to swap the list from 
	 * @param {String/Array} The DD Group(s) you want to swap the list to
	 */
	swapDDGroup : function(from,to){	
		this.removeFromDDGroup(from);
		this.addToDDGroup(to);		
		this.enable();
	},
	
	/**
	 * Function adds elements to a particular DD Group
	 * @public
	 * @param {String/Array} DD group(s) you want to add your list to
	 */
	addToDDGroup : function(groupName,enable){	
		if(typeof groupName != 'string'){
			var i = groupName.length-1;
			while(i>=0){				
				this.ddGroups[groupName[i]]=true;
				this.dragZone.addToGroup(groupName[i]);
				this.dropZone.addToGroup(groupName[i]);
				--i;
			}
		} else {			
			this.ddGroups[groupName]=true;	
			this.dragZone.addToGroup(groupName);
			this.dropZone.addToGroup(groupName);
		}
		if(typeof enable !== 'undefined' || enable){
			this.enable();
		}
	},
	/**
	 * Function removes a list from a particular DD Group
	 * @public
	 * @param {String/Array} DD group(s) you want to remove your list from
	 */
	removeFromDDGroup : function(groupName, enable){
		if(typeof groupName != 'string'){
			var i = groupName.length-1;
			while(i>=0){
				this.ddGroups[groupName[i]]=false;
				this.dragZone.removeFromGroup(groupName[i]);
				this.dropZone.removeFromGroup(groupName[i]);
				--i;
			}
		} else {
			this.ddGroups[groupName]=false;
			this.dragZone.removeFromGroup(groupName);
			this.dropZone.removeFromGroup(groupName);
		}
		if(typeof enable !== 'undefined' || enable){
			this.enable();
		}
	}
});


Ext.dd.SortableDragZone = function(el, config){		
	Ext.dd.DragZone.superclass.constructor.call(this, el, config);	
};

Ext.extend(Ext.dd.SortableDragZone, Ext.dd.DragZone, {

	getDragData : function(e){		
		return this.getHandleFromEvent(e,this.queryString);
  },
	
	onInitDrag : function(x, y){
		//var dragged = this.dragData.ddel.cloneNode(true);

		//dragged.id='';

		if(Ext.isIE){ //IE fix for checkbox and radio
			var array_cb = Ext.fly(this.dragData.ddel).select('input[type="checkbox"]');
			var array_rb = Ext.fly(this.dragData.ddel).select('input[type="radio"]');
			var i = 0;
			Ext.fly(dragged).select('input[type="checkbox"]').each(function() {
				this.dom.defaultChecked = array_cb.elements[i].checked;
				i++;
			});
			i = 0;
			Ext.fly(dragged).select('input[type="radio"]').each(function() {
				this.dom.defaultChecked = array_rb.elements[i].checked;
				i++;
			});
		}
		//move status change		
		this.onStartDrag(x, y);
		this.dragData.ddel.style.opacity='0.5';
		
		this.parentClass.fireEvent('initSortable',this);
		
		return true;
	},
	
	//hide the moues triggered clone element
	alignElWithMouse: function(){
		return false;
	},
  
	afterRepair : function(){               
        this.dragData.ddel.style.visibility='';
        this.dragging = false;
		this.parentClass.fireEvent('afterRepair',this);
    },
   
  getRepairXY : function(e){
        //uncomment this to show animation
        return Ext.Element.fly(this.dragData.ddel).getY(); 
    },
  
  getNodeData : function(e){			
        e = Ext.EventObject.setEvent(e);
        var target = e.getTarget(this.queryString);		
		if(target){			
            this.dragData.ddel = target.parentNode;
            this.dragData.single = true;
            return this.dragData;
        }
        return false;
    },
    
	onEndDrag : function(data, e){ 
		this.parentClass.fireEvent('endDrag',data, e);		
	},
	
	getHandleFromEvent : function(e,qs){		
		if(this.handles){			
			return Ext.dd.Registry.getHandleFromEvent(e);	
		} else {
			var t = e.getTarget(qs,this.queryDepth);
			return t ? Ext.dd.Registry.getHandle(t.id) : null;
		}
		
	}
});

Ext.dd.SortableDropZone = function(el, config){	
    Ext.dd.DropZone.superclass.constructor.call(this, el, config);
};

Ext.extend(Ext.dd.SortableDropZone, Ext.dd.DropZone, {
	
	notifyEnter : function(source, e, data){
		this.srcEl = Ext.get(data.ddel);
		if(this._testDDGroup()){    

		    if(this.srcEl !== null){
		        if(this.srcEl.dom.parentNode !== this.el.dom){
		            if(!Ext.query(this.queryString,this.el).length > 0 && this.srcEl.is(this.queryString)){
		                this.srcEl.appendTo(this.el);
		            }
		        }						
		        //add DD ok class to proxy            
		        if(this.overClass){
		            this.el.addClass(this.overClass);
		        }						
				this.parentClass.fireEvent('notifyEnter', source, e, data);
		        return this.dropAllowed;
		    }    
		}
	},

	onContainerOver : function(dd, e, data){		
    	if(this._testDDGroup()){
			this.parentClass.fireEvent('containerOver', dd, e, data);
			return this.dropAllowed;
		}	
	},

	notifyOver : function(dd, e, data){
		if(this._testDDGroup()){    
	    	var x;
   			
		    var n = this.getTargetFromEvent(e);
				
		    if(!n){					
		        if(this.lastOverNode){
		            this.onNodeOut(this.lastOverNode, dd, e, data);
		            this.lastOverNode = null;
		        }
		        return this.onContainerOver(dd, e, data);
		    }
		    if(this.lastOverNode != n){					
		        if(this.lastOverNode){
		            this.onNodeOut(this.lastOverNode, dd, e, data);
		        }
		        this.onNodeEnter(n, dd, e, data);
		        this.lastOverNode = n;
		    }
			this.parentClass.fireEvent('notifyOver',n, dd, e, data);
		    return this.onNodeOver(n, dd, e, data);
		}
	},


	onNodeOver : function(n, dd, e, data){
		if(this._testDDGroup()){
			if(this.horizonatal) {
				var x = e.getPageX();
				if (x < this.lastX) {
					this.goingPrevious = true;
				} else if (x > this.lastX) {
					this.goingPrevious = false;
				}
				this.lastX = x;
			} else {
				var y = e.getPageY();
				if (y < this.lastY) {
					this.goingPrevious = true;
				} else if (y > this.lastY) {
					this.goingPrevious = false;
				}
				this.lastY = y;
			}
			var destEl = Ext.get(n.ddel);

			if((Ext.isIE)&&(this.srcEl !== null)){ //IE fix for checkbox and radio
				this.srcEl.select('input[type="checkbox"]').each(function() {
					this.dom.defaultChecked = this.dom.checked;
				});
				this.srcEl.select('input[type="radio"]').each(function() {
					this.dom.defaultChecked = this.dom.checked;
				});
			}

			if (this.goingPrevious) {				
				this.srcEl.insertBefore(destEl);
			} else {
				this.srcEl.insertAfter(destEl);
			}
	
			this.parentClass.fireEvent('nodeOver',n, dd, e, data);
	
			return this.dropAllowed;
		} else {
			return this.dropNotAllowed;
		}
	},

	notifyDrop : function(dd, e, data){
	    if(this._testDDGroup){    
			if(this.srcEl !== null){
		        this.srcEl.setStyle('opacity','1.0');            
		        // refresh the drag drop manager
		        Ext.dd.DragDropMgr.refreshCache(this.groupName);
						//console.log(data);
				this.parentClass.fireEvent('notifyDrop',dd, e, data);
		    }
			return true;
		}    
	},
	_testDDGroup : function(){		
		var groupTest = Ext.dd.Registry.getTarget(this.srcEl.id).ddGroups;		
		var result = false;
		
		for(this.groups in groupTest){
			if(groupTest[this.groups]){
				result=true;	
			}
		}
		return result;
	}
});


//Ext override to stop error when unloading a page
Ext.dd.DragDropMgr._remove = function(oDD) {
	if(oDD){
	    for (var g in oDD.groups) {
	       if(this.ids[g]){
				if (g && this.ids[g][oDD.id]) {
	            	delete this.ids[g][oDD.id];
	        	}
			}
	    }
	    delete this.handleIds[oDD.id];
	}
}


//call the script and make available to grag & drop content dom
  Ext.onReady(function() {		
		var sortable = new Array();
		
		Ext.select('table.typo3-dblist tbody').each(function(el){
			//get the temp array of row items on initSortable
			var tempRowsInit 	= new Array();
			var tempRowsEnd  	= new Array();
			//get the dragzone container tablename
			var tablename 		= Ext.get(el.id).getAttribute('data-tablename');
			var vc 						= Ext.get(el.id).getAttribute('data-vc');
			var previd 				= Ext.get(el.id).getAttribute('data-prev');
			//current table item id
			var currentTableID = el.id;			
			
				//get the ids				
				getId = el.id;				
				sortable[getId] = new Ext.ux.Sortable({
						container : getId,						
						handles : true,
					  className : 'db_list_normal',
						tagName: 'tr',
						dragGroups : [
										'sideways'+getId
						]	
						//contextMenu : contextAlert
				});
				
				//get the array of rows
				sortable[getId].on('initSortable',function(){				
					tempRowsInit = this.serialise();				
				});				
				
				sortable[getId].on('endDrag',function(data,e){					
					tempRowsEnd = this.serialise();
					//get the dragged ID
					draggedID = data.ddel.id; 					
					//after Row itme EndDragged Client is going to change the moveemnt
					movementSave(tempRowsInit, tempRowsEnd, draggedID, tablename, vc, previd, currentTableID); 					
				});

				//get serialise objects
			
		});
		
		/** movement functionality control by this function
		 * @param {Array} the array of row organized before sort
		 * @param {Array} the array of rwo organized after sort
		 * @param {Integer} the dragged id
		 * @param {String} the name of dragzone area
		 * @returns return
		 */		
		var movementSave = function(tempRowsInit, tempRowsEnd, draggedID, tablename, vc, previd, currentTableID) {			
      //get the initial row arrangemnts
			var getTempRowTnit = _queryAttr(tempRowsInit);				
			//get the dragged rows arrangements
			var getTempTowEnd = _queryAttr(tempRowsEnd);				
			//get the dragged dom data id
			var draggedDataID =	Ext.get(draggedID).getAttribute('data-id');
			
			//get index for first arrangement
			var initIid = parseInt(getTempRowTnit.indexOf(draggedDataID));
			//get index of end arrangemnt
			var endIid = parseInt(getTempTowEnd.indexOf(draggedDataID));
			
			//check whether going downward
			if(endIid > initIid){
				//downward configs
				moveSign = '-';
				//get the value form init-arrangement using end-Index
				moveTo = getTempRowTnit[endIid];
				moveTo = moveSign + moveTo;				
				//execute ajax functionlity
				var ajax = moveAjax(tablename, draggedDataID, moveTo, vc, currentTableID);  				
			}
			
			//check whether movement is upward
			else if( initIid > endIid){				
				//upward means opposite action of downward
				//get the row id of replaced by draggred one
				upwardDid = parseInt(getTempTowEnd.indexOf(draggedDataID));
				//create the move command
				moveSign = '-';
				
				//if row dragged to the first possition to the container
				//if not normaly dragging 
				if(upwardDid == 0 && upwardDid != null){					
					replaceTo = previd; 
				}else{					
					replaceTo = moveSign + getTempRowTnit[upwardDid-1];
				}
				//execute ajax functionality
				var rajax = moveAjax(tablename, draggedDataID, replaceTo, vc, currentTableID)
			}			
    }
		
		
	/* get the data attributes to the serialized array  
	 * @param {array} the dom array with data attributes
	 * @returns {ojbect} return attributes as an objects
	 */
		var _queryAttr = function(array){
					var result = new Array();

					Ext.each(array, function(data,index){				
						var dataAttr =	Ext.get(data.id).getAttribute('data-id');
						//if row dom don't have data-id attribute just ingore 
						if(dataAttr){
							result[index] = dataAttr;																		
						}				
					});				
				return result; 
		}
	
		/* Ajax request to handle move operation 
		 * @param {string} the queries strings
		 * @returns {boolen} movemnent successfully chaned at TYPO3 databas
		 */
		var moveAjax = function(tablename, id, moveTo, vc, currentTableID){	
			//creae params cmd[tt_content][2][move]
			var cmd = 'cmd['+tablename+']['+id+'][move]';				
				//fire Ajax request to Class.tx_nsdynamicc_sort.php
				//loading indicator
				mask = new Ext.LoadMask(Ext.getBody(), {
             msg: "Saving Arrangements..."
         });
				//show the mask				
				mask.show();

				Ext.Ajax.request({
					url: 'ajax.php?ajaxID=tx_nsdynamicc::movementSort&'+cmd+'='+moveTo+'&prErr=1'+'&vC='+vc+'&uPT=1',					
					timeout: 3000,
					method: 'GET',
					success: function(xhr) {
						var obj = Ext.decode(xhr.responseText).response;
						//if successfully the ajax request just hide the mask
						if(obj){
							mask.hide();
						}
					}
				});
		}
	});