define(
    ['ko',
     'underscore'],
    function (ko, 
              _) {

        'use strict';
     
        return { 

            country_id_actual: ko.observable(''),
            departamento_id_actual: ko.observable(''),
            provincia_id_actual: ko.observable(''),
            distrito_id_actual: ko.observable(''),
            deliveryLocation_actual: ko.observable({}),

            country_id: ko.observable(''),
            departamento_id: ko.observable(''),
            provincia_id: ko.observable(''),
            distrito_id: ko.observable(''),
            ciudad_label: ko.observable(''),
            distancia: ko.observable(''),
            programado: ko.observable(''),
            movilidad: ko.observable('1'),
            deliveryLocation: ko.observable({})
        };

    }
);
