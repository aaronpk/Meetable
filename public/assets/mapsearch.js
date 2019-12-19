jQuery(function(){

    if(document.getElementById('map')) {
        var map = new google.maps.Map(document.getElementById('map'), {
            center: new google.maps.LatLng(45.5,-122.6),
            zoom: 8
        });

        var service = new google.maps.places.AutocompleteService();
        var places = new google.maps.places.PlacesService(map);
        var selectedPlacePin;

        function googleMapsTypeahead(input) {
            return new Promise((resolve, reject) => {
                service.getQueryPredictions({input: input}, function(predictions, status) {
                    if(status == google.maps.places.PlacesServiceStatus.OK) {
                        const autocomplete = predictions.map(function(item){
                            return {label: item.description, value: item.place_id};
                        }).filter(function(item){
                            return item.value;
                        });
                        resolve(autocomplete);
                    } else {
                        reject();
                    }
                });
            });
        }

        function googleMapsTypeaheadSelect(state) {
            places.getDetails({
                placeId: state.value,
                fields: ["geometry", "name", "address_component", "formatted_address", "url", "utc_offset"]
            }, function(result, status) {
                if(status != google.maps.places.PlacesServiceStatus.OK) {
                    alert('Error looking up location');
                    return;
                }

                if(selectedPlacePin) {
                    selectedPlacePin.setMap(null);
                    selectedPlacePin = null;
                }
                selectedPlacePin = new google.maps.Marker({
                    position: result.geometry.location,
                    map: map
                });

                address = '';
                locality = '';
                region = '';
                country = '';
                for(var i in result.address_components) {
                    if(result.address_components[i].types.includes('street_number')) {
                        address += ' '+result.address_components[i].short_name;
                    }
                    if(result.address_components[i].types.includes('route')) {
                        address += ' '+result.address_components[i].short_name;
                    }
                    if(result.address_components[i].types.includes('locality')) {
                        locality = result.address_components[i].long_name;
                    }
                    if(result.address_components[i].types.includes('administrative_area_level_1')) {
                        region = result.address_components[i].long_name;
                        if(region == locality) {
                            region = '';
                        }
                    }
                    if(result.address_components[i].types.includes('country')) {
                        country = result.address_components[i].long_name;
                    }
                }
                if(result.address_components[0].types.includes('subpremise')) {
                    address += ' #'+result.address_components[0].short_name;
                }
                address = address.trim();

                if(result.name != address) {
                    $("input[name=location_name]").val(result.name);
                } else {
                    $("input[name=location_name]").val("");
                }
                $("input[name=location_address]").val(address);
                $("input[name=location_locality]").val(locality);
                $("input[name=location_region]").val(region);
                $("input[name=location_country]").val(country);

                $("input[name=latitude]").val(result.geometry.location.lat());
                $("input[name=longitude]").val(result.geometry.location.lng());

                map.setCenter(result.geometry.location);
                map.setZoom(15);
                $("#location_preview").removeClass("hidden");

                // Trigger a timezone lookup
                $.post("/event/timezone", {
                    _token: csrf_token(),
                    latitude: result.geometry.location.lat(),
                    longitude: result.geometry.location.lng()
                }, function(response){
                    if($("option[value='"+response.timezone+"']").length) {
                        $("select[name=timezone]").val(response.timezone);
                    } else {
                        $("select[name=timezone]").append('<option value="'+response.timezone+'">'+response.timezone+'</option>');
                        $("select[name=timezone]").val(response.timezone);
                    }
                });

            });
        }

        bulmahead('location_search', 'location_menu', googleMapsTypeahead, googleMapsTypeaheadSelect, 200);
    }
});
