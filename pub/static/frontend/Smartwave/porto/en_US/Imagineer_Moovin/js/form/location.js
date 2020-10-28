define([
    'underscore',
    'uiRegistry',
    'ko',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/modal/modal'
],  function (_, uiRegistry, ko, Component) {
    'use strict';
   return Component.extend({       
        isShowLocationButton: ko.observable(false),        
        showLocationPopUp: function (){
          
   if (navigator.geolocation) {
       navigator.geolocation.getCurrentPosition(function(position){
           alert(position.coords.latitude + "  :  " + position.coords.longitude);
       });
    } else { 
       alert("Geolocation is not supported by this browser.");
    }

        },
        onUpdate: function(){
            var self = this;
            this._super();
            if(this.checkInvalid()){                                
                self.isShowLocationButton(false);
            } else {                
                self.isShowLocationButton(true);
            }
        }       
    });
});