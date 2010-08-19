// Create the class
var DcolumnClass = Class.create({
		
		initialize:function(){
				this.obj = '';
				// Is called when the page has finished loading by the Event.observe code below
		var dClickIcon = $$('.dcolumn'); 
		dClickIcon.each(function(Element){
        //call helper function to do other cool stuff
    		Element.observe("click", function(event) {
						this.obj = event.element();																
						var getData = this.obj.up().readAttribute("data-dinfo");				
						var getDataObject = getData.split(",");
								
				var getGroups = new Ajax.Request('ajax.php', {
					method: 'get',
					parameters: 'ajaxID=tx_nsdynamicc::createAjaxSelector&id='+getDataObject[0]+'&table='+getDataObject[1],
						onComplete: this.showPopup.bind(this)
						});						
				}.bind(this));										
    }.bind(this));
		},
		
		showPopup: function(xhr){
				var HTMLoutput = xhr.responseText;
				//alert(HTMLoutput);
				this.obj.up().addClassName("disply-list");
				this.addSelectorBox(HTMLoutput);
				//event.target.			
		},
		
		/**
	 * manipulates the DOM to add the divs needed for selector box at the bottom of the <body>-tag
	 *	 
	 */
		addSelectorBox: function(HTMLoutput){				
				//add the content to the bottom of the <body> tag				
				var offset = this.obj.up().cumulativeOffset();						
				//Add custom styles				
				var container = '<div id="contentselectbox" style="display: block;top: '+(offset[1]+16)+'px; right: 31px">'+HTMLoutput+'</div>';
				//select box wrapper container object
				var selector = $('contentselectbox');

				this.obj.up('body').insert({
						bottom: container
				});
				
				//bind the mousedown event
				document.observe("mousedown", this.checkMouse.bind(this));
		},
		
		checkMouse: function(event){
				//alert('1')
    //check the click was on selector itself or on selectorOwner
    var selector = "#contentselectbox";
    //var selectorParent = $(event.target).parents(selector).length;

		var selectorParent = 0;
				selectorParent = event.target.up(selector);
				//check the mouse only click on the out of the target eara
				if( event.target == $("contentselectbox")[0] ||  event.target == this.obj.up() || Object.isElement(selectorParent)){			
				return false;
		}
				this.hideSelector();   
		},
		
		//hide and remver the loaded popup record list 
		hideSelector: function(){				
    var selector = $("contentselectbox");
		
		//remove entire selector container form the document 
    document.stopObserving("mousedown");
    selector.remove();    
  }

		
});
		
// Global variable for the instance of the class
var dcolumn;
// Creating an instance of the class if the page has finished loading
Event.observe(window, "load", function() {
		dcolumn = new DcolumnClass();
});


//*************************************************************//
//ceate a class for collapse the record table suing Ajax and javascript

var CollapseClass = Class.create({
		
		initialize:function(){
				this.obj = '';
				this.collapsed = '';
				this.table	= '';
				this.recordName = '';
		//get the click object 
		var collapseIcon = $$('table.typo3-dblist thead td.col-icon a'); 
		collapseIcon.each(function(Element){
        //call helper function to do other cool stuff
    		Element.observe("click", function(event) {
						
						this.obj = event.element();																
						var getData = this.obj.up().readAttribute("data-collapse");
								
						var getDataObject = getData.split(",");
						
								this.table	= 'collapse['+ getDataObject[0] + ']';
								//assign the data values as variables
								this.recordName = getDataObject[0]; 
								this.collapsed =	getDataObject[1];
								
								
				var getGroups = new Ajax.Request('ajax.php', {
						method: 'get',
						parameters: 'ajaxID=tx_nsdynamicc::updateAjaxCollapse&'+this.table+'='+ this.collapsed,
						onComplete: this.showHideTable.bind(this)
				});
				
						return false;
				}.bind(this));										
    }.bind(this));
		},
		//show or hide the record table 
		showHideTable: function(xhr){
        var HTMLoutput = xhr.responseText;
				//to hide the record table
				if(this.collapsed == 1){
						this.obj.up('.typo3-dblist').down('tbody').hide();
						this.obj.removeClassName("t3-icon-view-list-collapse").addClassName("t3-icon-view-list-expand");
            this.obj.up().writeAttribute("title",HTMLoutput);
						//reset the data in click object
						this.resetTableClickData(0);				
				}
				if(this.collapsed == 0){
						this.obj.up('.typo3-dblist').down('tbody').show();
						this.obj.removeClassName("t3-icon-view-list-expand").addClassName("t3-icon-view-list-collapse");
            this.obj.up().writeAttribute("title",HTMLoutput);
						//reset the data in click object
						this.resetTableClickData(1);
				}

		},
		
		resetTableClickData: function(collapseOption){
				data = this.recordName+','+collapseOption;
				this.obj.up().writeAttribute("data-collapse",data);
		}
});
		
// Global variable for the instance of the class
var collapse;
// Creating an instance of the class if the page has finished loading
Event.observe(window, "load", function() {
		collapse = new CollapseClass();
});
			