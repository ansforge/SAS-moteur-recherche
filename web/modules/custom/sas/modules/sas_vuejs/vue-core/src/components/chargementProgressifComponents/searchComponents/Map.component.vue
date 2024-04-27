<template>
  <div class="map-wrapper">
    <button
      v-if="showRelaunchBtn"
      class="recenter relaunch-search show-btn sas-relaunch-search"
      type="button"
      @click.prevent="researchInBounds"
    >
      Relancer la recherche
    </button>

    <div id="mapbox_sas_vuejs" class="mapbox-container" />
  </div>
</template>

<script>
import {
  ref,
  computed,
  watch,
  onMounted,
  onUnmounted,
  nextTick,
} from 'vue';
import { storeToRefs } from 'pinia';

import _isEmpty from 'lodash.isempty';
import _isEqual from 'lodash.isequal';
import {
  useSearchData,
  useUserData,
  useGeolocationData,
} from '@/stores';
import { useLeafletMap } from '@/composables';

export default {
  props: {
    currentDisplayedList: {
      type: Array,
      default: () => ([]),
    },
    isPageOne: {
      type: Boolean,
      default: false,
    },
    isClusterDisplayed: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    'clicked-map-cluster',
    'clicked-map-marker',
    'research-in-bounds',
    'mouseleave-map-marker',
    'mouseenter-map-marker',
  ],
  setup(props, { emit }) {
    const searchDataStore = useSearchData();
    const userDataStore = useUserData();
    const geolocationStore = useGeolocationData();
    const {
      geolocation,
      hasFailed: geolocationHasFailed,
    } = storeToRefs(geolocationStore);

    const {
      drawBluePoint,
      deleteBluePoint,
      drawCircleInMap,
      fitCircleInMap,
      getLayer,
     } = useLeafletMap(window.L);

    /** @type {import('vue').Ref<import('leaflet').Map>} */
    const currentMap = ref({});
    const showRelaunchBtn = ref(false);

    const cardsOnMap = ref([]);
    const currentBounds = ref([]);

    const markersBoundsWithAnimation = null;

    // layer for markers
    const markerLayer = window.L.layerGroup();

    const currentUser = computed(() => userDataStore.currentUser);

    // all cards to be displayed
    const currentPoints = computed(() => searchDataStore.currentList.reduce((acc, curr) => acc.concat(curr), []));

    // center point
    const centerPoint = computed(() => {
      if (geolocationHasFailed.value) {
        // zoom on FRA
        return {
          lat: 46.5286,
          lng: 2.4389,
        };
      }

      return {
        lat: geolocation.value.latitude,
        lng: geolocation.value.longitude,
      };
    });

    const drawCircle = (options) => {
      if (!currentMap.value) return false;

      drawCircleInMap(currentMap.value, options);
      return true;
    };

    const deleteBluePointLayer = () => {
      if (!currentMap.value) return false;

      deleteBluePoint(currentMap.value);
      return true;
    };

    /**
     * Fit the viewport to contain the currently drawn circle. Do nothing if no circle have been drawn yet
     * @param {number} padding
     */
    const fitBoundsToCircle = (padding = 30) => {
      const circleLayer = getLayer('circle');

      if (!circleLayer) {
        console.error("Can't find circle layer");
        return false;
      }

      currentMap.value.fitBounds(circleLayer.getBounds(), { padding: [padding, padding] });
      return true;
    };

    /**
     * draw map with initial config
     * @param {import('leaflet')} L
     */
    const init = (L) => {
      const { apiKey, tileUrl } = window.drupalSettings?.sas_vuejs?.parameters?.maptiler_settings || {};

      if (!apiKey) {
        console.error('MapTiler API key is missing');
      }

      // Init Map
      const settings = {
        latitude: centerPoint.value.lat,
        longitude: centerPoint.value.lng,
        zoom: 6,
        mapUrl: `${tileUrl}?key=${apiKey}`,
        attribution: '\u003ca href="https://www.maptiler.com/copyright/" target="_blank"\u003e\u0026copy; MapTiler\u003c/a\u003e \u003ca href="https://www.openstreetmap.org/copyright" target="_blank"\u003e\u0026copy; OpenStreetMap contributors\u003c/a\u003e',
        blueIcon: null,
        markers: null,
      };

      const map = L.map('mapbox_sas_vuejs', {
        zoomControl: false,
        wheelPxPerZoomLevel: 150,
        zoomDelta: 0.9,
        zoomSnap: 0,
      });

      new L.Control.Zoom({ position: 'topright' }).addTo(map);

      map.on('load', () => {
        map.invalidateSize();
        const mapContainer = document.querySelector('.mapbox-container');
        let containerResizeSensorLock = null;
        window.ResizeSensor(mapContainer, (() => {
          if (containerResizeSensorLock !== null) {
            clearTimeout(containerResizeSensorLock);
          }
          containerResizeSensorLock = setTimeout(() => {
            containerResizeSensorLock = null;
            map.invalidateSize();
          }, 250);
        }));

        // We used the mousedown because of the first load
        map.on('mousedown', () => {
          map.on('moveend', () => {
            showRelaunchBtn.value = true;
          });
        });
      });

      settings.blueIcon = L.divIcon({
        className: 'sante-leaflet-marker',
        iconSize: null,
      });

      map.setView([settings.latitude, settings.longitude], settings.zoom);

      L.tileLayer(settings.mapUrl, {
        tileSize: 512,
        zoomOffset: -1,
        minZoom: 1,
        maxZoom: 22,
        attribution: settings.attribution,
        crossOrigin: true,
      }).addTo(map);

      map.addLayer(markerLayer);

      currentMap.value = map;
    };

    // init map on component mount
    onMounted(() => {
      init(window.L);
      window.addEventListener('map::askFlyTo', flyToMarker);
    });

    onUnmounted(() => {
      window.removeEventListener('map::askFlyTo', flyToMarker);
    });

    /**
     * place markers on map
     */
    function placeMarkers(marker, map) {
      if (_isEmpty(map)) return;

      // SOS Medecin markers are not placed on map
      if (!marker.isSOSMedecin) {
        // eslint-disable-next-line no-unused-expressions
        marker.isCluster
          ? createClusterMarker(marker)
          : createSingleMarker(marker, map);
      }

      // set correct bounds according to markers
      if (
        currentBounds.value.length
        && markersBoundsWithAnimation
      ) {
        markersBoundsWithAnimation();
      }

      currentMap.value = map;
    }

    /**
     * create a single marker
     * @param {Object} marker
     * @param {Object} map
     */
    function createSingleMarker(marker, map) {
      let timeoutId = null;
      let isPopupShown = false;
      const currentCard = currentPoints.value.find((card) => card?.its_nid === marker?.properties?.id) || {};

      // eslint-disable-next-line new-cap
      const popup = new window.L.popup({
        maxWidth: 250,
        closeButton: false,
      }).setContent(createMarkerPopup(currentCard));

      const myIcon = window.L.divIcon({
        className: 'leaflet-marker-icon leaflet-custom-marker',
        iconSize: null,
      });

      // dispatch popup open event
      map.on('popupopen', (e) => {
        // eslint-disable-next-line no-underscore-dangle
        const clikedMarker = e.popup?._source?._icon;
        // eslint-disable-next-line no-unused-expressions
        clikedMarker?.classList?.add('active');
      });

      // dispatch popup close event
      map.on('popupclose', (e) => {
        // eslint-disable-next-line no-underscore-dangle
        const clikedMarker = e.popup?._source?._icon;
        // eslint-disable-next-line no-unused-expressions
        clikedMarker?.classList?.remove('active');
      });

      const markerEl = new window.L.Marker(marker.coordinates, { icon: myIcon }).bindPopup(popup);
      markerLayer.addLayer(markerEl);

      // eslint-disable-next-line no-underscore-dangle
      const customMarker = markerEl._icon;
      markerEl.off('click');
      customMarker.className = `${getPinImageClass(marker.isLrmPreferredDoctor, marker.slotLists, [currentCard.its_nid])} ${customMarker.className}`;

      customMarker.dataset.markerId = marker.properties.id;
      if (marker.properties) {
        customMarker.dataset.icon = marker.properties.icon;
        customMarker.dataset.availability = marker.properties.availability;
      }

      customMarker.addEventListener('click', () => {
        if (!markerEl.isPopupOpen() || isPopupShown) {
          markerEl.openPopup();
          isPopupShown = false;
        } else {
          markerEl.closePopup();
        }
        clearTimeout(timeoutId);
        emit('clicked-map-marker', {
          content: marker,
          elemId: marker?.properties?.id || '',
          highlight: true,
        });
      });

      customMarker.addEventListener('mouseenter', () => {
        timeoutId = setTimeout(() => {
          markerEl.openPopup();
          isPopupShown = true;
        }, 500);

        emit('mouseenter-map-marker', {
          content: marker,
          elemId: marker?.properties?.id || '',
          highlight: true,
        });
      });

      customMarker.addEventListener('mouseleave', () => {
        clearTimeout(timeoutId);
        if (isPopupShown) markerEl.closePopup();

        emit('mouseleave-map-marker', {
          content: marker,
          elemId: marker?.properties?.id || '',
          highlight: false,
        });
      });

      return markerEl;
    }

    /**
     * create a cluster
     * @param {Object} marker
     */
    function createClusterMarker(marker) {
      const myIcon = window.L.divIcon({
        className: 'leaflet-marker-icon leaflet-custom-marker',
        iconSize: null,
      });

      const markerEl = new window.L.Marker(marker.coordinates, { icon: myIcon });
      markerLayer.addLayer(markerEl);
      // eslint-disable-next-line no-underscore-dangle
      const customMarker = markerEl._icon;
      customMarker.dataset.clusterGroupId = marker.properties?.id;
      const currentIds = marker.properties?.id.split('-').map((elId) => parseInt(elId, 10));
      customMarker.className = `${getPinImageClass(marker.isLrmPreferredDoctor, marker.slotLists, currentIds)} ${customMarker.className}`;

      if (marker.properties?.icon) {
        customMarker.dataset.icon = marker.properties.icon;
      }

      if (props.currentActiveItem?.length) {
        customMarker.classList.add('active');
      }

      customMarker.addEventListener('click', () => {
        emit('clicked-map-cluster', marker);
      });

      return markerEl;
    }

    watch([geolocation, currentUser], () => {
      // If the user isn't authorized or if the search isn't a precise one, do nothing
      if (!currentUser.value.isRegulateurOSNPorIOA || geolocation.value?.type !== geolocationStore.GEOLOCATION_TYPE.ADDRESS) return;

      drawBluePoint(currentMap.value, {
        latitude: geolocation.value.latitude,
        longitude: geolocation.value.longitude,
        streetLabel: geolocationStore.streetLabel,
        cityLabel: geolocationStore.cityLabel,
      });
    }, { immediate: true });

    /**
     * get marker icon
     * @param {String} type
     */
    function getIconType(type) {
      let iconId;

      // 1 => icon asip-icon asip-icon-hospital
      // 2 => icon asip-icon asip-icon-stethoscope
      // 3 => icon asip-icon asip-icon-pharmacie

      switch (type) {
        case 'entite_geographique':
        case 'health_institution':
        case 'care_deals':
          iconId = 1;
          break;

        case 'professionnel_de_sante':
          iconId = 2;
          break;

        case 'service_de_sante':
        case 'finess_institution':
          iconId = 3;
          break;

        default:
          iconId = 2;
          break;
      }

      return iconId.toString();
    }

    /**
     * get marker pin class
     * @param {Boolean} isLrmPreferredDoctor
     * @param {Object} datas
     */
    function getPinImageClass(isLrmPreferredDoctor, datas, currentId = []) {
      const markerDisplayedClass = isElementVisible(currentId)
      ? 'marker-is-displayed'
      : '';

      // Preferred doctor pin
      if (isLrmPreferredDoctor) {
        return `marker-single-position-preferred-doctor ${props.isPageOne ? 'marker-is-displayed' : ''}`;
      }

      // pas de data
      if (!datas || !datas.length) return `marker-single-position ${markerDisplayedClass}`;

      const isTodayExist = datas.findIndex((x) => x.today.length > 0);

      const isSlotsExists = datas.findIndex((x) => x.tomorrow.length || x.afterTomorrow.length);
      // On a des créneaux aujourd'hui
      if (isTodayExist > -1) return `marker-single-position-current ${markerDisplayedClass}`;

      // Des créneaux demain ou après demain disponible
      if (isSlotsExists > -1) return `marker-single-position-soon ${markerDisplayedClass}`;

      // Pas de créneaux
      if (isSlotsExists <= -1) return `marker-single-position ${markerDisplayedClass}`;

      return '';
    }

    // create marker popup
    function createMarkerPopup(popupContent = {}) {
      const nid = popupContent?.its_nid;
      const namePs = popupContent?.tm_X3b_und_title;
      let titlesPs = popupContent?.tm_X3b_und_field_profession_name?.join(' ') || popupContent?.sm_establishment_type_names;

      if (popupContent?.tm_X3b_und_establishment_type_names?.length) {
        const hasPharmacie = popupContent.tm_X3b_und_establishment_type_names.some((el) => (el.toLowerCase() === 'pharmacie'));

        titlesPs = hasPharmacie
        ? 'Pharmacie'
        : popupContent?.tm_X3b_und_establishment_type_names[0];
      }

      const numTel = popupContent?.final_phone_number;

      const offreLabel = popupContent?.sm_field_custom_label_permanent_label;

      const templateTitlePs = titlesPs
      ? `<div class="subtitle">${titlesPs}</div>`
      : '';
      const templateNumTel = !numTel
      ? ''
      : `<div class="item--icon tel">
          <i class="asip-icon asip-icon-telephone">
            <span class="sr-only">Téléphone</span>
          </i>
          <span>${numTel}</span>
        </div>`;
      let labels = '';
      let templateContributionSas = '';

      if (offreLabel) {
        labels = '<div class="labels-wrapper">';

        offreLabel.forEach((label) => {
          labels
            += `<div class='label--item'>${label}</div>`;
        });
        labels += '</div>';
      }

      if (
        currentUser.value.isRegulateurOSNP
        && popupContent.bs_sas_participation
        ) {
        templateContributionSas = `<div class="contribution-sas">${popupContent.sasParticipationLabel}</div>`;
      }

      if (
        currentUser.value.isRegulateurIOA
        && popupContent.bs_sas_forfait_reo
        ) {
        templateContributionSas += `<div class="contribution-sas">${popupContent.sasForfaitReuLabel}</div>`;
      }

      return `<div class="card-popup">
        <div class="popup--wrapper">
          <div class="popup--content">
            <div class="map-pin-item map-res-${nid} type-2">
              <div class="name-ps">${namePs}</div>
            </div>
            ${templateTitlePs}
            ${labels}
            ${templateNumTel}
            ${templateContributionSas}
          </div>
        </div>
      </div>`;
    }

    /**
     * get marker details
     * @param {Object} currentContents
     * @param {String} markerPosition
     */
    function getCurrentPointDetails(currentContents, markerPosition) {
      const isCluster = currentContents.length > 1;
      const currentLatLng = markerPosition.split(',');
      const pointId = isCluster
      ? currentContents.map((card) => card.its_nid).join('-')
      : currentContents[0]?.its_nid || null;

      if (currentContents.length) {
        const currentPoint = {
          type: 'point',
          properties: {
            icon: getIconType(currentContents[0]?.ss_type || ''),
            id: pointId,
          },
          coordinates: [parseFloat(currentLatLng[0]), parseFloat(currentLatLng[1])],
          slotLists: currentContents.map((x) => x.slotList),
          isSOSMedecin: currentContents[0]?.isSOSMedecin || false,
          isLrmPreferredDoctor: currentContents[0]?.isLrmSearchWithPreferredDoctor || false,
          isCluster,
        };

        if (isCluster) {
          currentPoint.properties.group = [];
          currentPoint.properties['items_count'] = currentContents.length;

          currentContents.forEach((content) => {
            currentPoint.properties.group.push({
              type: 'point',
              properties: {
                icon: getIconType(content.ss_type || ''),
                title: content.tus_title || '',
                id: content.its_nid || null,
              },
              coordinates: [parseFloat(currentLatLng[0]), parseFloat(currentLatLng[1])],
            });
          });
        } else {
          currentPoint.properties.title = currentContents[0]?.tus_title || '';
        }

        return currentPoint;
      }
        return {};
    }

    /**
     * update current list of markers and clusters
     * @param {Array} filteredMarkers
     */
    function updateCurrentPoints(filteredMarkers = []) {
      const groupByCoords = {};

      // group markers by latlng
      filteredMarkers.forEach((card) => {
        if (card.locs_field_geolocalisation_latlon) {
          if (!groupByCoords[card.locs_field_geolocalisation_latlon]) {
            groupByCoords[card.locs_field_geolocalisation_latlon] = [];
          }
          groupByCoords[card.locs_field_geolocalisation_latlon].push(card);
          cardsOnMap.value.push(card.its_nid);
        }
      });

      // create a point for every latlng
      // eslint-disable-next-line guard-for-in
      for (const markerPosition in groupByCoords) {
        const currentContents = groupByCoords[markerPosition];
        const currentPoint = getCurrentPointDetails(currentContents, markerPosition);

        // SOS Medecin markers are included in map bounds calculation, Preferred doctor is excluded
        if (!currentPoint.isLrmPreferredDoctor) {
          currentBounds.value.push(currentPoint.coordinates);
        }

        placeMarkers(currentPoint, currentMap.value);
      }
    }

    /**
     * highlight marker on card hover
     * @param markerData
     */
    function handleCardHover(markerData) {
      const markerId = markerData.markerId ? markerData.markerId : '';
      const listMarkers = document.querySelectorAll('.marker-single-position, .marker-single-position-current, .marker-single-position-soon, marker-single-position-preferred-doctor');

      listMarkers.forEach((marker) => {
        const currentMarkerId = marker.dataset.markerId;

        if (parseInt(currentMarkerId, 10) === markerId) {
          // eslint-disable-next-line no-unused-expressions
          marker.classList.contains('active') ? marker.classList.remove('active') : marker.classList.add('active');
        } else {
          marker.classList.remove('active');
        }
      });
    }

    /**
     * is the current page has a given marker
     * @param {Number} currentIds
     */
    function isElementVisible(currentIds) {
      return currentIds.some((currId) => props.currentDisplayedList.includes(currId));
    }

    watch(() => props.currentDisplayedList, setCurrentDisplayedMarkers);

    /**
     * manage marker-is-displayed class on markers
     */
    function setCurrentDisplayedMarkers() {
      document.querySelectorAll('.leaflet-custom-marker').forEach((el) => {
        el.classList.remove('marker-is-displayed');

        if (
          el.dataset.markerId
          && props.currentDisplayedList.includes(parseInt(el.dataset.markerId, 10))
        ) {
          el.classList.add('marker-is-displayed');
        }

        if (el.dataset?.clusterGroupId) {
          const currentIds = el.dataset.clusterGroupId.split('-').map((elId) => parseInt(elId, 10));
          const isClusterElementVisible = isElementVisible(currentIds);

          if (isClusterElementVisible) {
            el.classList.add('marker-is-displayed');
          }
        }
      });
    }

    /**
     * emit current bounds on relaunch search btn click
     */
    function researchInBounds() {
      const { latitude, longitude, radius } = fitCircleInMap(currentMap.value);

      emit('research-in-bounds', { latitude, longitude, radius });
      showRelaunchBtn.value = false;
    }

    /**
     * reset marker layer and bound values
     */
    function clearMarkerLayer() {
      currentBounds.value = [];
      cardsOnMap.value = [];
      markerLayer.clearLayers();
    }

    /**
     * zoom into given marker on map
     * @param {Object} evtData
     */
    function flyToMarker(evtData) {
      if (!evtData?.detail) return;

      const lat = parseFloat(evtData.detail?.coords?.lat);
      const lng = parseFloat(evtData.detail?.coords?.lng);

      currentMap.value.flyTo(
        {
          lat,
          lng,
        },
        15,
        {
          animate: true,
          duration: 0.5,
          easeLinearity: 0.1,
        },
      );
    }

    // when new points to display change, add them to the map
    watch(
[currentPoints, searchDataStore.currentSelectedFilters],
      // eslint-disable-next-line no-unused-vars
      ([newCards, newFilters], [prevCards, prevFilters]) => {
        if (_isEmpty(currentMap.value)) return;

        nextTick(() => {
          if (
            props.isClusterDisplayed
            || _isEqual(newFilters, prevFilters)
          ) {
            clearMarkerLayer();
          }
          // filter for cards which are not on the map
          const unusedCards = newCards.filter((card) => !cardsOnMap.value.includes(card.its_nid));
          if (unusedCards.length) {
            updateCurrentPoints(unusedCards);
          }
        });
      },
);

    // on custom filter change, reset markers and layers
    watch(() => searchDataStore.customFilters, clearMarkerLayer);

    return {
      showRelaunchBtn,
      currentPoints,
      currentMap,
      centerPoint,
      currentBounds,
      cardsOnMap,
      currentUser,
      handleCardHover,
      researchInBounds,

      // actions
      deleteBluePointLayer,
      drawCircle,
      fitBoundsToCircle,
    };
  },
};
</script>
