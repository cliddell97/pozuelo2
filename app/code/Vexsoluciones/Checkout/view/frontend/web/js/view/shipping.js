/*browser:true*/
/*global define*/

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry',
    'mage/translate',
    'Magento_Checkout/js/model/shipping-rate-service',
    'Vexsoluciones_Checkout/js/model/ubigeo',
    'Vexsoluciones_Checkout/js/model/shipping-rate-processor/ubigeo',
    'Magento_Checkout/js/model/shipping-rate-processor/customer-address'


], function ($,
        _,
        Component,
        ko,
        customer,
        customerData,
        addressList,
        addressConverter,
        quote,
        createShippingAddress,
        selectShippingAddress,
        shippingRatesValidator,
        formPopUpState,
        shippingService,
        selectShippingMethodAction,
        rateRegistry,
        setShippingInformationAction,
        stepNavigator,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t,
        shippingRateService,
        ubigeoCheckout,ratesUbigeo,customerAddressProcessor) {
    'use strict';
    let popUp = null;

    function getRootUrl() {

        return window.location.origin 
                        ? window.location.origin + '/'
                        : window.location.protocol + '/' + window.location.host + '/';
    
    }


    return Component.extend({

        defaults: {
            template: 'Magento_Checkout/shipping',
            shippingFormTemplate: 'Magento_Checkout/shipping-address/form',
            shippingMethodListTemplate: 'Magento_Checkout/shipping-address/shipping-method-list',
            shippingMethodItemTemplate: 'Magento_Checkout/shipping-address/shipping-method-item'
        },
 
        checkDelay: 2000,
        checkRequest: null,
        isEmailCheckComplete: null,
        isCustomerLoggedIn: customer.isLoggedIn,
        forgotPasswordUrl: window.checkoutConfig.forgotPasswordUrl,
        emailCheckTimeout: 0,
        modeFormAccount : ko.observable(1),
        isCreateAccount : false,

        programado : ko.observable('9-13'),
        movilidad : ko.observable('1'),

        ubigeoPais : ko.observable(),
        currentCountry : ko.observableArray(),
        isCountryLocal : ko.observable(true),
        programadoaux : ko.observable(false),
        departamentos: ko.observableArray(),
        ubigeoDepartamento: ko.observable(),
        provincias: ko.observableArray(),
        ubigeoProvincia: ko.observable(),
        distritos: ko.observableArray(),
        ubigeoDistrito: ko.observable(),

        distanciaMenor : ko.observable(0),
        mapGeoCode : null,
        marcadorDelivery : null,
        marcadorCoordenadas : ubigeoCheckout.deliveryLocation,
        verificartienda : null,
        verificareventoend : null,
        tiendascoordenadas : ko.observable('-11.1,-77.605'),

        visible: ko.observable(!quote.isVirtual()), // VEX false
        errorValidationMessage: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),
        isFormInline: addressList().length == 0,
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isNewAddressAdded: ko.observable(false),
        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,

        direccionActualCalle : ko.observable(),
        direccionActualDepartamento : ko.observable(),
        direccionActualReferencia : ko.observable(),
            
        /**
         * Load data from server for shipping step
         */
        navigate: function () {
            //load data from server for shipping step
        },

        /**
         * @return {*}
         */
        getPopUp: function () {
            var self = this,
                buttons;
            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    },
                    {
                        text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                        class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',

                        /** @inheritdoc */
                        click: this.onClosePopUp.bind(this)
                    }
                ];
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                };
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        /**
         * Save new shipping address
         */
        saveNewAddress: function () {
            var addressData,
                newShippingAddress;

            this.source.set('params.invalid', false);
            this.source.trigger('shippingAddress.data.validate');

              if (!this.source.get('params.invalid')) {
                addressData = this.source.get('shippingAddress');
                // if user clicked the checkbox, its value is true or false. Need to convert.
                addressData.save_in_address_book = this.saveInAddressBook ? 1 : 0;

                if(ubigeoCheckout.country_id() == "CR"){
                    
                    const ciudad_label = this.ubigeoDistrito().text + ', ' + this.ubigeoDepartamento().text;
                   
                    ubigeoCheckout.departamento_id(this.ubigeoDepartamento().id);
                    ubigeoCheckout.provincia_id(this.ubigeoProvincia().id);
                    ubigeoCheckout.distrito_id(this.ubigeoDistrito().id);
                    ubigeoCheckout.ciudad_label(ciudad_label);

                    ubigeoCheckout.country_id_actual(ubigeoCheckout.country_id());
                    ubigeoCheckout.departamento_id_actual(ubigeoCheckout.departamento_id());
                    ubigeoCheckout.provincia_id_actual(ubigeoCheckout.provincia_id());
                    ubigeoCheckout.distrito_id_actual(ubigeoCheckout.distrito_id());
                    ubigeoCheckout.deliveryLocation_actual(ubigeoCheckout.deliveryLocation());


                    $('.checkout-new-address-city-label').html(ciudad_label);


                    const ubigeo =  ubigeoCheckout.distrito_id();

                    addressData.country_id = ubigeoCheckout.country_id();
                    addressData.postcode = ubigeo;
                    addressData.region_code = ubigeo;
                    addressData.regionCode = ubigeo;
                    addressData.city = ciudad_label;

                    addressData.custom_attributes = {
                                departamento_id : {
                                    attribute_code: "departamento_id",
                                    value: this.ubigeoDepartamento().id
                                },
                                provincia_id : {
                                    attribute_code: "provincia_id",
                                    value: this.ubigeoProvincia().id
                                },
                                distrito_id : {
                                    attribute_code: "distrito_id",
                                    value: this.ubigeoDistrito().id
                                },
                                departamento_label : {
                                    attribute_code: "departamento_label",
                                    value: this.ubigeoDepartamento().text
                                },
                                provincia_label : {
                                    attribute_code: "provincia_label",
                                    value: this.ubigeoProvincia().text
                                },
                                distrito_label : {
                                    attribute_code: "distrito_label",
                                    value: this.ubigeoDistrito().text
                                }
                            };
                }else{

                    addressData.country_id = ubigeoCheckout.country_id();
                    addressData.postcode = "0-0-0";
                    addressData.region_code = "0-0-0";
                    addressData.regionCode = "0-0-0";

                    if(typeof addressData!= "undefined" && typeof addressData.custom_attributes!= "undefined" && typeof addressData.custom_attributes.distrito_id!= "undefined" ){
                        delete addressData.custom_attributes.departamento_id;
                        delete addressData.custom_attributes.provincia_id;
                        delete addressData.custom_attributes.distrito_id;
                        delete addressData.custom_attributes.departamento_label;
                        delete addressData.custom_attributes.provincia_label;
                        delete addressData.custom_attributes.distrito_label;
                    }
                    
                }

                //.customAttributes.departamento_label = 'holadasd';
                
                // New address must be selected as a shipping address
                newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                this.getPopUp().closeModal();
                this.isNewAddressAdded(true);

                
                //console.log(address);
                /*var type = quote.shippingAddress().getType();                        
                let address = quote.shippingAddress();
                address.customAttributes = {
                            departamento_label : {
                                attribute_code: "departamento_label",
                                value: "5"
                            },
                            provincia_label : {
                                attribute_code: "provincia_label",
                                value: "5"
                            },
                            distrito_label : {
                                attribute_code: "distrito_label",
                                value: "5"
                            }
                        };

                console.log(address);*/

            }
        },
        getByMethodCode : function(method_code){

                let rates = this.rates();
                for( let i = 0; i < rates.length; i++ ){
                    if(rates[i].method_code == method_code ){
                        return rates[i];
                    }
                }

            },

            isSelected: ko.computed(function () {
                    return quote.shippingMethod() ?
                        quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code
                        : null;
                }
            ),

            /**
             * @param {Object} shippingMethod
             * @return {Boolean}
             */
            selectShippingMethod: function (shippingMethod) {
                selectShippingMethodAction(shippingMethod);
                checkoutData.setSelectedShippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code); 
                return true;
            },

            /**
             * Set shipping information handler
             */
            setShippingInformation: function () {
                 
                if (this.validateShippingInformation()) {
 
                    setShippingInformationAction().done(
                        function () {
                            stepNavigator.next();
                        }
                    );
                }
                
            },


            getCurrentShipping : function() {

            },
            /**
             * @return {Boolean}
             */
            validateShippingInformation: function () {
                let ataux = quote.shippingAddress().customAttributes;
                if(typeof ataux != "undefined" && typeof ataux.departamento_id != "undefined"){

                    var ciudad_label = quote.shippingAddress().city;
                    if(typeof ataux.distrito_label != "undefined" && typeof ataux.departamento_label != "undefined" && typeof ataux.provincia_label != "undefined"){
                        ciudad_label = ataux.distrito_label.value + ', ' + ataux.departamento_label.value;                   
                    }
                    ubigeoCheckout.departamento_id(ataux.departamento_id.value);
                    ubigeoCheckout.provincia_id(ataux.provincia_id.value);
                    ubigeoCheckout.distrito_id(ataux.distrito_id.value);
                    ubigeoCheckout.ciudad_label(ciudad_label);

                    ubigeoCheckout.country_id_actual(ubigeoCheckout.country_id());
                    ubigeoCheckout.departamento_id_actual(ubigeoCheckout.departamento_id());
                    ubigeoCheckout.provincia_id_actual(ubigeoCheckout.provincia_id());
                    ubigeoCheckout.distrito_id_actual(ubigeoCheckout.distrito_id());
                }

                
                var shippingAddress,
                    addressData,
                    loginFormSelector = 'form[data-role=email-with-possible-login]',
                    emailValidationResult = customer.isLoggedIn();
  

                if (!quote.shippingMethod()) { 
                    this.errorValidationMessage('Please specify a shipping method.');
                    return false;
                }
  
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
 
                

                //if(this.is_delivery()){ 
  
                    if (this.isFormInline) {
 
                        this.source.set('params.invalid', false);
                        this.source.trigger('shippingAddress.data.validate');

                        if (this.source.get('shippingAddress.custom_attributes')) {
                            this.source.trigger('shippingAddress.custom_attributes.data.validate');
                        }

                        if (this.source.get('params.invalid') ||
                            !quote.shippingMethod().method_code ||
                            !quote.shippingMethod().carrier_code ||
                            !emailValidationResult
                        ) {
                            return false;
                        }

                        shippingAddress = quote.shippingAddress();
                      
                        addressData = addressConverter.formAddressDataToQuoteAddress(
                            this.source.get('shippingAddress')
                        );
 

                        //Copy form data to quote shipping address object
                        for (var field in addressData) {

                            if (addressData.hasOwnProperty(field) &&
                                shippingAddress.hasOwnProperty(field) &&
                                typeof addressData[field] != 'function' &&
                                _.isEqual(shippingAddress[field], addressData[field])
                            ) {
                        
                                shippingAddress[field] = addressData[field];

                            } else if (typeof addressData[field] != 'function' &&
                                !_.isEqual(shippingAddress[field], addressData[field])) {
                            
                                shippingAddress = addressData;
                                break;
                            }
                        }

                        shippingAddress.countryId = ubigeoCheckout.country_id();

                        if (customer.isLoggedIn()) {
                            shippingAddress.save_in_address_book = 1;
                        }

                        selectShippingAddress(shippingAddress);
                    
                    }
                    else
                    {
                        // si no es formulario de primera direccion  
                        shippingAddress = quote.shippingAddress();
  
                        // Si esta logeado y la lista de direcciones es mayor a cero 
                        let calle = shippingAddress.street ? shippingAddress.street[0] : '';
                        let direccionActualDepartamento = '';
                        let direccionActualReferencia = '';




                        if( customer.isLoggedIn() ){
                        
                            if( addressList().length > 0){

                                calle = this.direccionActualCalle();
                                direccionActualDepartamento = this.direccionActualDepartamento();
                                direccionActualReferencia = this.direccionActualReferencia();

                                //if(calle != ''){

                                    /*shippingAddress.street = [
                                        calle,
                                        direccionActualDepartamento,
                                        direccionActualReferencia
                                    ];*/ 

                                    // Para evitar que por cada orden se registre la direccion de nuevo
                                    //shippingAddress.save_in_address_book = 0; // 1
                                     
                                    selectShippingAddress(shippingAddress);
                                //}

                            }
                        }

 
                    }

 
                if (!emailValidationResult) {
                    $(loginFormSelector + ' input[name=username]').focus();

                    return false;
                }

                return true;
            },
        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },
        
        initialize: function () {
            this._super();

            let hasNewAddress,
                fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';


            if (!quote.isVirtual()) {
                    stepNavigator.registerStep(
                        'shipping',
                        '',
                        $t('Shipping'),
                        this.visible, _.bind(this.navigate, this),
                        10
                    );
                }
            checkoutDataResolver.resolveShippingAddress();

            hasNewAddress = addressList.some( (address) => {
                return address.getType() == 'new-customer-address';
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe( (value) => {
                if (value) {
                    this.getPopUp().openModal();
                }
            });

            quote.shippingMethod.subscribe( () => {
                this.errorValidationMessage(false);
            });

            registry.async('checkoutProvider')( (checkoutProvider) => {

                const shippingAddressData = checkoutData.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }
                checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                    checkoutData.setShippingAddressFromData(shippingAddressData);
                });
                shippingRatesValidator.initFields(fieldsetName);
            });



            var self = this;
            var rqUbigeo = null;


            this.ubigeoPais.subscribe(function(newValue){
                
                self.isCountryLocal(false);
                ubigeoCheckout.country_id(newValue.id);
                ubigeoCheckout.country_id_actual(newValue.id);


                rqUbigeo =  $.ajax({
                    url: getRootUrl()+'rest/default/V1/listarDepartamentos/'+newValue.id,
                    data: JSON.stringify({}),
                    showLoader: true,
                    type: 'GET',
                    dataType: 'json',
                    context: this, 
                    async : false,
                    beforeSend: function(request) {
                        request.setRequestHeader('Content-Type', 'application/json');
                    },
                    success: function(response){
                 
                        let departamentosTmp = [];
                        for(let i = 0; i < response.length; i++){
                            departamentosTmp.push({'id' : response[i].id,
                                                   'text': response[i].nombre });
						//	console.log(response[i].id  + " - " + response[i].nombre);
						}
                        
                        self.departamentos(departamentosTmp);
                        if(typeof departamentosTmp[0] != "undefined"){
                            self.ubigeoDepartamento(departamentosTmp[0].id);
                            self.isCountryLocal(true);
                        }
                        

                    },

                    complete: function () { 
                         
                    }
                });

                self.calcularDistancias();
            });
            

            this.ubigeoDepartamento.subscribe(function(newValue) {
 
                let departamento_id = newValue && newValue.id;

                ubigeoCheckout.departamento_id(departamento_id);
                ubigeoCheckout.departamento_id_actual(departamento_id);
                if(rqUbigeo != null){
                    rqUbigeo.abort();
                }

                rqUbigeo = $.ajax({
                    url: getRootUrl()+'rest/default/V1/listarProvincias/' + departamento_id,
                    data: JSON.stringify({}),
                    showLoader: false,
                    type: 'GET',
                    dataType: 'json',
                    context: this, 
                    beforeSend: function(request) {
                        request.setRequestHeader('Content-Type', 'application/json');
                    },
                    success: function(response){

                        let provinciasTmp = [];

                        for(let i = 0; i < response.length; i++){
                              
                            provinciasTmp.push({'id' : response[i].id,
                                                'text': response[i].nombre });

                        }
                            
                        self.provincias(provinciasTmp);
                        self.ubigeoProvincia(provinciasTmp[0]);
                         

                    },
                    complete: function () { 
                         
                    }
                });
            });

            this.ubigeoProvincia.subscribe(function(newValue) {

                let provincia_id = newValue && newValue.id; 

                ubigeoCheckout.provincia_id(provincia_id);
                ubigeoCheckout.provincia_id_actual(provincia_id);
                if(rqUbigeo != null){
                    rqUbigeo.abort();
                }

                rqUbigeo = $.ajax({

                    url: getRootUrl() + 'rest/default/V1/listarDistritos/'+provincia_id,
                    data: JSON.stringify({}),
                    showLoader: false,
                    type: 'GET',
                    dataType: 'json',
                    context: this, 
                    beforeSend: function(request) { 
                        request.setRequestHeader('Content-Type', 'application/json');
                    },
                    success: function(response) {
                         
                       let distritosTmp = [];

                       for(let i = 0; i < response.length; i++){
                             
                           distritosTmp.push({'id' : response[i].id,
                                              'text': response[i].nombre });

                           //self.ubigeoDistrito(distritosTmp[0]);
                       }
                           
                       self.distritos(distritosTmp);
                       self.ubigeoDistrito(distritosTmp[0]);
        
                       let indDistrito = 0;

                    },

                    complete: function () { 
                         
                    }
                });
            });

            this.ubigeoDistrito.subscribe(function(newValue) {
            
                    let departamento_id = self.ubigeoDepartamento() && self.ubigeoDepartamento().id;
                    let provincia_id = self.ubigeoProvincia() && self.ubigeoProvincia().id;
                    let distrito_id = newValue && newValue.id;  
					
					let regionCode =  distrito_id;// + '-'+distancia+'-'+self.movilidad()+'-'+self.marcadorCoordenadas().lat+","+self.marcadorCoordenadas().lng;
					
					var elem = document.getElementsByName("postcode")[0];
					elem.value = regionCode;
					ko.utils.triggerEvent(elem, "change");
					elem.disabled = true;
					
                    //self.generarMapaDelivery();
                    
                    self.calcularDistancias();

                    ubigeoCheckout.distrito_id(distrito_id);
                    ubigeoCheckout.distrito_id_actual(distrito_id); 

            });
            

            $.ajax({
                url: getRootUrl()+'rest/default/V1/listarTienda',
                data: JSON.stringify({}),
                showLoader: true,
                type: 'GET',
                dataType: 'json',
                context: this, 
                async : false,
                beforeSend: function(request) {
                    request.setRequestHeader('Content-Type', 'application/json');
                },
                success: function(response){
                    self.tiendascoordenadas(response[0]);
                },

                complete: function () { 
                     
                }
            });

            $.ajax({
                url: getRootUrl()+'rest/default/V1/listarPaises',
                data: JSON.stringify({}),
                showLoader: true,
                type: 'GET',
                dataType: 'json',
                context: this, 
                async : false,
                beforeSend: function(request) {
                    request.setRequestHeader('Content-Type', 'application/json');
                },
                success: function(response){
             
                    let paisesTmp = [];

                    for(let i = 0; i < response.length; i++){
                        paisesTmp.push({'id' : response[i].id,
                                               'text': response[i].nombre });
                    }

                    self.currentCountry(paisesTmp);
                    console.log(paisesTmp[0]);
                    if(typeof paisesTmp[0] != "undefined"){
                        self.ubigeoPais(paisesTmp[0]);
                    }

                },

                complete: function () { 
                     
                }
            });
            
        

             setTimeout(function(){ document.getElementsByName('postcode').disabled = true; }, 5000);            

        },
        click: function(e){
            ubigeoCheckout.programado(this.programado());   
            return true;
        },
        clickmovilidad: function(e){
            ubigeoCheckout.movilidad(this.movilidad());
            this.refrescarCostosPorDistancia();
            return true;
        },

        generarMapaDelivery : function(){

            let self = this;  

            if(this.mapGeoCode==null){
                $("#contenedor-mapa").show();
                this.mapGeoCode = new google.maps.Map(document.getElementById('mapaGeoDecode'), {
                      zoom: 15,
                      center: {lat: -12.1103058, lng: -77.0513356}
                });

                this.geocoder = new google.maps.Geocoder();
            }

            let address =  self.ubigeoDistrito().text+ ',' + self.ubigeoProvincia().text+','+self.ubigeoDepartamento().text;

            this.coberturas = [];

            this.geocoder.geocode({'address': address}, function(results, status) {
            
              if (status === 'OK') {

                try{

                    self.mapGeoCode.setCenter(results[0].geometry.location);

                    self.marcadorCoordenadas({ lat: results[0].geometry.location.lat(), 
                                               lng: results[0].geometry.location.lng() });  //results[0].geometry.location;

                    if(self.marcadorDelivery==null){
                        self.marcadorDelivery = new google.maps.Marker({
                              map: self.mapGeoCode,
                              position: results[0].geometry.location,
                              draggable: true
                        });
                    }else{
                        
                        self.marcadorDelivery.setPosition(results[0].geometry.location);
                    }

                    self.marcadorCoordenadas({lat: results[0].geometry.location.lat(), lng: results[0].geometry.location.lng() });
                    self.calcularDistancias();

                }
                catch(err){

                    console.log(err);
                }

                if(self.verificareventoend==null){
                    self.verificareventoend = true;
                    self.marcadorDelivery.addListener('dragend', function(event){
                         self.marcadorCoordenadas({lat: event.latLng.lat(), lng: event.latLng.lng() });
                         self.calcularDistancias();
                         console.log(event.latLng);
                    });
                }
                

             
              } else {
            
                console.log('Geocode was not successful for the following reason: ' + status);
            
              }
            
            });


        },

        calcularDistancias : function(){

                   let self = this;
                   let service = new google.maps.DistanceMatrixService;
                   let destinos = [];

                    if(self.tiendascoordenadas()=="" || self.tiendascoordenadas()==null){
                        self.tiendascoordenadas("0,0");
                    }
                    let tienda = self.tiendascoordenadas().split(',');

                   destinos.push({ lat: parseFloat(tienda[0]), lng: parseFloat(tienda[1]) });
                    let tiendaalmacen = new google.maps.Marker({
                          map: self.mapGeoCode,
                          icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                          position: { lat: parseFloat(tienda[0]), lng: parseFloat(tienda[1]) }
                    });


                   service.getDistanceMatrix({

                         origins: [this.marcadorCoordenadas()],
                         destinations: destinos,
                         travelMode: 'DRIVING',
                         unitSystem: google.maps.UnitSystem.METRIC,
                         avoidHighways: false,
                         avoidTolls: false
                   
                   }, function(response, status) { 

                          
                         if (status !== 'OK') {
                            
                            self.distanciaMenor( 0 );
                            ubigeoCheckout.distancia(0);
                         }  
                         else
                         {

                            let distanciaMenor = 0;
                            let indexDistanciaMenor = 0;
                            let aux = 0;

                            $.each( response.rows[0].elements, function (index, data) {
                            
                               let distanciaKm = data.distance.text.replace(",",".").split(' ');
 
                                let distanciaFLoat = parseFloat(distanciaKm[0]); 

                                if(distanciaMenor == 0 || distanciaFLoat < distanciaMenor ){
                                    distanciaMenor = distanciaFLoat;
                                    indexDistanciaMenor = index;
                                }

                                aux = distanciaFLoat;
                            });

                            self.distanciaMenor( aux );
                            ubigeoCheckout.distancia(aux);
                            

                            if(addressList().length==0){
                                self.refrescarCostosPorDistancia();
                            }

                         }
 
                   }); 

        },

        refrescarCostosPorDistancia: function(){
  
                let self = this;
    
                let departamento_id = this.ubigeoDepartamento() && this.ubigeoDepartamento().id;
                let provincia_id = this.ubigeoProvincia() && this.ubigeoProvincia().id;
                let distrito_id = this.ubigeoDistrito() && this.ubigeoDistrito().id;
 
                let distancia = self.distanciaMenor();

                ubigeoCheckout.country_id_actual(ubigeoCheckout.country_id());
                ubigeoCheckout.departamento_id_actual(ubigeoCheckout.departamento_id());
                ubigeoCheckout.provincia_id_actual(ubigeoCheckout.provincia_id());
                ubigeoCheckout.distrito_id_actual(ubigeoCheckout.distrito_id());
                ubigeoCheckout.deliveryLocation_actual(ubigeoCheckout.deliveryLocation());

                let regionCode =  distrito_id;// + '-'+distancia+'-'+self.movilidad()+'-'+self.marcadorCoordenadas().lat+","+self.marcadorCoordenadas().lng;
           
                var type = quote.shippingAddress().getType();                        
                let address = quote.shippingAddress();
                
                address.regionCode = regionCode;
                address.postcode = regionCode;
                address.countryId = self.ubigeoPais().id;
                //address.auxiliar = departamento_id;
                 
                ratesUbigeo.getRates(address).done(function(){

                    //self.selectShippingMethod(self.getByMethodCode('envio_express'));
                    
                });
                

            }




    });
});
