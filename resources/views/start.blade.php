<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <link href="css/styles.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

        <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.2/mapbox-gl.js"></script>
        <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.2/mapbox-gl.css" rel="stylesheet" />
        <style>
            
        </style>
    </head>
    <body>
        <div class="container my-5">
            <header>
                <h1 align="center" class="display-1">Weather App</h1>
            </header>    
        </div>
        <div class="row">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                <div class="explorer">
                    <div id="map" class="explorer-map"></div>
                    <div class="explorer--text">
                        <input
                        type="text"
                        class="explorer--search explorer--background-icon explorer--text"
                        id="explorer-search"
                        placeholder="Search Foursquare Places"
                        />
                        <div id="explorer-dropdown">
                            <ul id="explorer-suggestions"></ul>
                            <div id="explorer-error" class="explorer--error explorer--background-icon">
                                Something went wrong. Please refresh and try again.
                            </div>
                            <div id="explorer-not-found" class="explorer--error explorer--background-icon"></div>
                            <div class="explorer--copyright">
                                <img src="https://files.readme.io/7835fdb-powerByFSQ.svg" alt="powered by foursquare" />
                            </div>
                        </div>
                    </div>
                </div>
                <div id="weather-error"></div>
                <div id="location-box">
                <h2 id="location-title" class="display-4"></h2>
                <p class="display-6">Sunrise: <span id="sunrise"></span> | Sunset: <span id="sunset"></span></p>
            </div>
            <div id="weather-box"></div>
            </div>
            <div class="col-sm-2"></div>
            <footer style="text-align: center">
                <p>
                    Author: Robert John Guloy<br />
                    <a href="mailto:robertjohnguloy@gmail.com">robertjohnguloy@gmail.com</a>
                </p>
                <p>Copyright &copy; 2024</p>
            </footer>
        </div>
        
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script>
            $(document).ready(function(){

                function loadLocalMapSearchJs() {
                    mapboxgl.accessToken = "{{ env('API_MAPBOX', '') }}";
                    const fsqAPIToken = "{{ env('API_FOURSQUARE', '') }}";
                    
                    let userLat = 40.7128;
                    let userLng = -74.0060;
                    let sessionToken = generateRandomSessionToken();
                    const inputField = document.getElementById('explorer-search');
                    const dropDownField = document.getElementById('explorer-dropdown');
                    const ulField = document.getElementById('explorer-suggestions');
                    const errorField = document.getElementById('explorer-error');
                    const notFoundField = document.getElementById('explorer-not-found');

                    const onChangeAutoComplete = debounce(changeAutoComplete);
                    inputField.addEventListener('input', onChangeAutoComplete);
                    ulField.addEventListener('click', selectItem);


                    function success(pos) {
                        const { latitude, longitude } = pos.coords;
                        userLat = latitude;
                        userLng = longitude;
                        flyToLocation(userLat, userLng);
                        
                    }

                    function logError(err) {
                        console.warn(`ERROR(${err.code}): ${err.message}`);
                    }

                    navigator.geolocation.getCurrentPosition(success, logError, {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0,
                    });

                    const map = new mapboxgl.Map({
                        container: 'map',
                        style: 'mapbox://styles/mapbox/light-v10',
                        center: [userLng, userLat],
                        zoom: 12,
                    });

                    map.addControl(new mapboxgl.GeolocateControl());
                    map.addControl(new mapboxgl.NavigationControl());

                    let currentMarker;

                    /* Generate a random string with 32 characters.
                    Session Token is a user-generated token to identify a session for billing purposes. 
                    Learn more about session tokens.
                    https://docs.foursquare.com/reference/session-tokens
                    */
                    function generateRandomSessionToken(length = 32) {
                        let result = '';
                        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                        for (let i = 0; i < length; i++) {
                            result += characters[Math.floor(Math.random() * characters.length)];
                        }
                        return result;
                    }

                    let isFetching = false;
                    async function changeAutoComplete({ target }) {
                        const { value: inputSearch = '' } = target;
                        ulField.innerHTML = '';
                        notFoundField.style.display = 'none';
                        errorField.style.display = 'none';
                        if (inputSearch.length && !isFetching) {
                            try {
                                isFetching = true;
                                const results = await autoComplete(inputSearch);
                                if (results && results.length) {
                                    results.forEach((value) => {
                                    addItem(value);
                                    });
                                } else {
                                    notFoundField.innerHTML = `Foursquare can't
                                    find ${inputSearch}. Make sure your search is spelled correctly.  
                                    <a href="https://foursquare.com/add-place?ll=${userLat}%2C${userLng}&venuename=${inputSearch}"
                                    target="_blank" rel="noopener noreferrer">Don't see the place you're looking for?</a>.`;
                                    notFoundField.style.display = 'block';
                                }
                            } catch (err) {
                                errorField.style.display = 'block';
                                logError(err);
                            } finally {
                                isFetching = false;
                                dropDownField.style.display = 'block';
                            }
                        } else {
                            dropDownField.style.display = 'none';
                        }
                    }

                    async function autoComplete(query) {
                        const { lng, lat } = map.getCenter();
                        userLat = lat;
                        userLng = lng;
                        try {
                            const searchParams = new URLSearchParams({
                                query,
                                types: 'place',
                                ll: `${userLat},${userLng}`,
                                radius: 50000,
                                session_token: sessionToken,
                            }).toString();
                            const searchResults = await fetch(
                            `https://api.foursquare.com/v3/autocomplete?${searchParams}`,
                            {
                                method: 'get',
                                headers: new Headers({
                                    Accept: 'application/json',
                                    Authorization: fsqAPIToken,
                                }),
                            }
                            );
                            const data = await searchResults.json();
                            return data.results;
                        } catch (error) {
                            throw error;
                        }
                    }

                    function addItem(value) {
                        const placeDetail = value[value.type];
                        if (!placeDetail || !placeDetail.geocodes || !placeDetail.geocodes.main) return;
                        const { latitude, longitude } = placeDetail.geocodes.main;
                        const fsqId = placeDetail.fsq_id;
                        const dataObject = JSON.stringify({ latitude, longitude, fsqId });
                        ulField.innerHTML +=
                        `<li class="explorer--dropdown-item" data-object='${dataObject}'>
                            <div>${highlightedNameElement(value.text)}</div>
                            <div class="explorer--secondary-text">${value.text.secondary}</div>
                        </li>`;
                    }

                    async function selectItem({ target }) {
                    if (target.tagName === 'LI') {
                        const valueObject = JSON.parse(target.dataset.object);
                        const { latitude, longitude, fsqId } = valueObject;
                        const placeDetail = await fetchPlacesDetails(fsqId);
                        addMarkerAndPopup(latitude, longitude, placeDetail);
                        flyToLocation(latitude, longitude);

                        // generate new session token after a complete search
                        sessionToken = generateRandomSessionToken();
                        const name = target.dataset.name;
                        inputField.value = target.children[0].textContent;
                        dropDownField.style.display = 'none';
                    }
                    }

                    async function fetchPlacesDetails(fsqId) {
                        try {
                            const searchParams = new URLSearchParams({
                            fields: 'fsq_id,name,geocodes,location,photos,rating',
                            session_token: sessionToken,
                            }).toString();
                            const results = await fetch(
                            `https://api.foursquare.com/v3/places/${fsqId}?${searchParams}`,
                            {
                                method: 'get',
                                headers: new Headers({
                                Accept: 'application/json',
                                Authorization: fsqAPIToken,
                                }),
                            }
                            );
                            const data = await results.json();
                            return data;
                        } catch (err) {
                            logError(err);
                        }
                    }

                    function createPopup(placeDetail) {
                        const { location = {}, name = '', photos = [], rating } = placeDetail;
                        let photoUrl = 'https://files.readme.io/c163d6e-placeholder.svg';
                        if (photos.length && photos[0]) {
                            photoUrl = `${photos[0].prefix}56${photos[0].suffix}`;
                        }
                        const popupHTML = `<div class="explorer--popup explorer--text">
                            <image class="explorer--popup-image" src="${photoUrl}" alt="photo of ${name}"/>
                            <div class="explorer--popup-description">
                            <div class="explorer--bold">${name}</div>
                            <div class="explorer--secondary-text">${location.address}</div>
                            </div>
                            ${rating ? `<div class="explorer--popup-rating">${rating}</div>` : `<div />`}
                        </div>`;

                        const markerHeight = 35;
                        const markerRadius = 14;
                        const linearOffset = 8;
                        const verticalOffset = 8;
                        const popupOffsets = {
                            top: [0, verticalOffset],
                            'top-left': [0, verticalOffset],
                            'top-right': [0, verticalOffset],
                            bottom: [0, -(markerHeight + verticalOffset)],
                            'bottom-left': [0, (markerHeight + verticalOffset - markerRadius + linearOffset) * -1],
                            'bottom-right': [0, (markerHeight + verticalOffset - markerRadius + linearOffset) * -1],
                            left: [markerRadius + linearOffset, (markerHeight - markerRadius) * -1],
                            right: [-(markerRadius + linearOffset), (markerHeight - markerRadius) * -1],
                        };
                        return new mapboxgl.Popup({
                            offset: popupOffsets,
                            closeButton: false,
                        }).setHTML(popupHTML);
                    }

                    function addMarkerAndPopup(lat, lng, placeDetail) {
                        if (currentMarker) currentMarker.remove();
                        currentMarker = new mapboxgl.Marker({
                            color: '#3333FF',
                        })
                            .setLngLat([lng, lat])
                            .setPopup(createPopup(placeDetail))
                            .addTo(map);

                        currentMarker.togglePopup();
                    }

                    function flyToLocation(lat, lng) {
                        map.flyTo({
                            center: [lng, lat],
                        });
                        $.get(`/weather/`+lat+`/`+lng, function(data){
                            data = JSON.parse(data);
                            showWeather(data);
                        }, 'json');
                    }

                    function setDate(value) {
                        const months = ["January", "February", "March", "April", "May", "June", 
                        "July", "August", "September", "October", "November", "December"];
                        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                        const d = new Date(value);
                        let day = days[d.getDay()];
                        let date = d.getDate();
                        let year = d.getFullYear();
                        let month = months[d.getMonth()];
                        let hour = d.getHours();
                        let minutes = "0" + d.getMinutes();

                        return (day + " " + month + " " + date + ", " + year + " " + hour + ":" + minutes.substr(-2));

                    }

                    function setTime(timestamp) {
                        let date = new Date(timestamp * 1000);
                        
                        let hours = date.getHours();

                        let minutes = "0" + date.getMinutes();

                        let formattedTime = hours + ':' + minutes.substr(-2);

                        return formattedTime;
                    }
                    function showWeather(data) {
                        
                        if (data.cod == 400 || data.cod == 404) {
                            $('#weather-error').innerHTML = "No valid location.";
                            return;
                        }
                        let location = data.city.name + ", " + data.city.country;
                        let sunrise = setTime(data.city.sunrise);
                        let sunset = setTime(data.city.sunset);
                        $('#location-title').text(location);
                        $('#sunrise').text(sunrise);
                        $('#sunset').text(sunset);
                        let elem = `<table class="table table-striped table-dark">
                        <tr>
                            <th>Date Time</th>
                            <th>Icon</th>
                            <th>Weather</th>
                            <th>Temp</th>
                            <th>Max Temp</th>
                            <th>Min Temp</th>
                        </tr>`;
                        
                        data.list.forEach(function(list){
                            elem += `<tr>
                                <td>${setDate(list.dt_txt)}</td>
                                <td><img src="https://openweathermap.org/img/wn/${list.weather[0].icon}.png" /></td>
                                <td>${list.weather[0].main} - ${list.weather[0].description}</td>
                                <td>${list.main.temp}&deg;C</td>
                                <td>${list.main.temp_max}&deg;C</td>
                                <td>${list.main.temp_min}&deg;C</td>
                            </tr>`;
                        });
                        elem += "</table>";
                        $('#weather-box').html(elem);
                    }

                    function highlightedNameElement(textObject) {
                        if (!textObject) return '';
                        const { primary, highlight } = textObject;
                        if (highlight && highlight.length) {
                            let beginning = 0;
                            let hightligtedWords = '';
                            for (let i = 0; i < highlight.length; i++) {
                            const { start, length } = highlight[i];
                            hightligtedWords += primary.substr(beginning, start - beginning);
                            hightligtedWords += '<b>' + primary.substr(start, length) + '</b>';
                            beginning = start + length;
                            }
                            hightligtedWords += primary.substr(beginning);
                            return hightligtedWords;
                        }
                        return primary;
                    }

                    function debounce(func, timeout = 300) {
                        let timer;
                        return (...args) => {
                            clearTimeout(timer);
                            timer = setTimeout(() => {
                            func.apply(this, args);
                            }, timeout);
                        };
                    }
                }

                loadLocalMapSearchJs();
            });
        </script>
    </body>
</html>