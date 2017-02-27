define([
    'listings',
],

  function(listings){
      
    // Create the base module for the page
    var re = angular.module('realestate', ['ui.bootstrap']);
    
    // Init the controllers, directives, and services for all the components
    // on the page
    listings.init(re);
    
    // Bootstrap the page
    angular.bootstrap(document, ['realestate']);
});