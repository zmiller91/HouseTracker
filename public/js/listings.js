define([
], function(){return{init: function(app) {

        app.controller("ListingsCtrl", function(ListingsData, $uibModal, $scope)
        {
            $scope.listings = [];
            $scope.houseTypes = [];
            $scope.appliedFilter = null;
            
            $scope.filter = function() {
                $uibModal.open({
                    templateUrl: "html/filter.html",
                    controller: "FilterCtrl",
                    size: 'md',
                    resolve: {
                        filter: function() {
                            // copy
                            return JSON.parse(JSON.stringify($scope.appliedFilter));
                        },
                        
                        houseTypes: function() {
                            return $scope.houseTypes;
                        }
                    }
                })
                .result.then(function(filter){
                    $scope.appliedFilter = filter;
                    update();
                });
            };

            var update = function() {
                
                if($scope.houseTypes.length === 0) {
                    ListingsData.getHouseTypes(
                        function(types) {
                            $scope.houseTypes = types;
                        }
                    );
                }
                
                ListingsData.get($scope.appliedFilter, function(listings) {
                    for(var l in listings) {
                        
                        // Get all the keys that start with 'distances_'
                        var distances = Object.keys(listings[l]).filter(function(k) {
                            return k.indexOf('distances_') == 0;
                        }).reduce(function(newData, k) {
                            newData[k.replace("distances_", "")] = listings[l][k];
                            return newData;
                        }, {});
                        
                        listings[l]['distances'] = ListingsData.chunk(distances, 3);
                    }
                    
                    $scope.listings = listings;
                });
            };
            angular.element(document).ready(update);
        })

        app.controller("FilterCtrl", ['$scope', '$uibModalInstance', 'filter', 'houseTypes',
            'ListingsData', function($scope, $uibModalInstance, filter, houseTypes, ListingsData)
        {
            
            $scope.selected = filter && filter['type[]'] || [];
            $scope.houseTypes = [];
            $scope.ratings = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            $scope.filter = filter || {
                min_price: null,
                max_price: null,
                min_sqft: null,
                min_year: null,
                min_beds: null,
                min_baths: null,
                es: null, 
                ms: null, 
                hs: null,
                distances: "zm | 11030 Circle Point Rd #350, Westminster, CO 80020 | 15 \ncb | 720 S Colorado Blvd #800n, Denver, CO 80246 | 15 ",
                not_city: null
            };
            
            $scope.apply = function(){
                $scope.filter['type[]'] = $scope.selected;
                $uibModalInstance.close($scope.filter);
            };

            $scope.cancel = function(){
                $uibModalInstance.dismiss("cancel");
            };

            // Helper method to get selected houseTypes
            $scope.selectedTypes = function() {
              return filterFilter($scope.houseTypes, { selected: true });
            };

            // Watch houseTypes for changes
            $scope.$watch('houseTypes', function (types) {
                
                var selected = []
                for(var r in types) {
                    for(var t in types[r]) {
                        if(types[r][t]['value']) {
                            selected.push(types[r][t]['type']);
                        }
                    }
                }
                
              $scope.selected = selected;
            }, true);
            
            var init = function(houseTypes) {
            
                for(var t in houseTypes) {
                    var type = {type: houseTypes[t], value: false};
                    if($scope.selected.indexOf(houseTypes[t]) > -1) {
                        type.value = true;
                    }

                    $scope.houseTypes.push(type);
                }

                $scope.houseTypes = ListingsData.chunk($scope.houseTypes, 4);
            };
            
            init(houseTypes);
        }])
    
        .directive("listings", function() {
          return {
            templateUrl: 'html/listings.html'
          };
        })

        .service('ListingsData', ['$http', function($http) 
        {
            this.listings = [];
            this.houseTypes = [];
            
            // Generic GET request
            this.get = function(params, success, error)
            {
                var $this = this;
                $http.get('api/listings', {params:params}).then(
                    function(response) 
                    {
                        $this.listings = response.data;
                        if(success){success($this.listings);};
                    },  
                    error
                );
            };
            
            // Generic GET request
            this.getHouseTypes = function(success, error)
            {
                var $this = this;
                $http.get('api/housetypes').then(
                    function(response) 
                    {
                        $this.houseTypes = response.data;
                        if(success){success($this.houseTypes);};
                    },  
                    error
                );
            };
            
            this.chunk = function(arr, size) {
                
                var chunkedKeys = [];
                var keys = Object.keys(arr);
                for (var i=0; i< keys.length; i+=size) {
                    chunkedKeys.push(keys.slice(i, i+size));
                }
                
                var retval = [];
                for(var c in chunkedKeys) {
                    var map = {};
                    for(var k in chunkedKeys[c]) {
                        map[chunkedKeys[c][k]] = arr[chunkedKeys[c][k]];
                    }
                    
                    retval.push(map);
                }
                
                return retval;
            }
        }]);
}};});