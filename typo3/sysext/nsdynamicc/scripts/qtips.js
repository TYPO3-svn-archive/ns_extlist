/*!
 * Ext JS Library 3.2.1
 * Copyright(c) 2006-2010 Ext JS, Inc.
 * licensing@extjs.com
 * http://www.extjs.com/license
 */

Ext.onReady(function(){
    //create array of helptips
    var helptips = new Array();
    
    Ext.select('a.typo3-csh-link').each(function(el){
        //get the id of each doms
        getTipID = el.id;
        //alert(getTipID);
        //remove actoin of help link
        Ext.get(getTipID).on('click', function(e){
            e.stopEvent();  
        });
        //get the data attributes to a js array
        var paragraph   = Ext.get(getTipID).getAttribute('data-paragraph');
        var headerTip      = Ext.get(getTipID).getAttribute('data-header');
        var url      = Ext.get(getTipID).getAttribute('href');
        
        //add the read more icon
        var helpIcon = '<a href="'+url+'" class="t3-csh-readmore"><span class="t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-go-forward"></span></a>';
        var paragraph = (paragraph!=null)? paragraph+ helpIcon: helpIcon; 
        
        //show the ToolTip
        new Ext.ToolTip({
            target: getTipID,
            title: headerTip,
            html: paragraph,                      
            anchor: 'left',
            autoHide: false,            

            listeners: {
            'render': function(){
                this.body.on('click', function(e){
                    e.stopEvent();                    
                    this.hide();
                    //load the help documents in Window
                      var win = new  Ext.Window({
                        width: 600,
                        height: 400,
                        id:'autoload-win',                        
                        //style  : { maxHeight: '400' },
                        autoScroll:true,                        
                        closeAction  : 'close',
                        autoLoad:{
                            url: url
                        },
                        title: headerTip,
                        /*tbar:[{
                            text:'Reload',
                        handler:function() {
                            win.load(win.autoLoad.url + '?' + (new Date).getTime());
                        }
                        }],*/
                        listeners:{
                            'show': function() {
                                this.loadMask = new Ext.LoadMask(this.body, {
                                    msg:'Loading. Please wait...'
                                });
                                //close the window ouside of the window
                            },
                            'render': function(){
                                this.body.on('click', function(e,target){
                                   e.stopEvent();
                                   //get the url
                                   var externalUrls = Ext.get(target).getAttribute('href');
                                   if(externalUrls != null){
                                        //load the new page content
                                        win.load({
                                            url: externalUrls,
                                            nocache: false
                                            });
                                        //win.autoScroll(true);
                                   }
                                });
                            }
                         
                        }
                        
                     });
                     win.show();
                     win.addListener('click', function(){
                        alert('am');                                        
                    });
                }, this, {delegate:'a'});
            }
            }
          });
            
    });
    
    //render disply information modle window for recor item
     Ext.select('a.typo3-disply-info').on('click', function(e,traget){
        e.stopEvent();
        infoUrl = traget.up().href;
        var headerInfo = 'View Item';
        
        var win = new  Ext.Window({
          width: 400,
          height: 400,
          id:'autoload-infowin',                                  
          autoScroll:true,                        
          closeAction  : 'close',
          bodyStyle: { padding: '0 10px' },
          autoLoad:{
              url: infoUrl
          },
          title: headerInfo,
          listeners:{
              'show': function() {
                  this.loadMask = new Ext.LoadMask(this.body, {
                      msg:'Loading. Please wait...'
                  });
                  //close the window ouside of the window
              }           
          }
          
       });
        win.show();
    });
                        
    Ext.QuickTips.init();   
});