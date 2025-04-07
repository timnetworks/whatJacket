# whatJacket 🧥

A simple PHP web application that suggests clothing based on the weather forecast for a given US ZIP code and a selected activity category.

**[Live Demo](https://whatjacket.timnetworks.net/)**

![Screenshot of whatJacket](https://raw.githubusercontent.com/timnetworks/whatJacket/refs/heads/main/img/screenshot.png)

## Features

*   Fetches current and 12-hour forecast data from the NOAA/NWS API.
*   Geocodes US ZIP codes to latitude/longitude using the Nominatim (OpenStreetMap) API.
*   Suggests clothing items based on:
    *   Temperature (current and 12-hour range)
    *   Probability of Precipitation
    *   Wind Speed
    *   Specific weather conditions (rain, snow, sun, etc.)
    *   Selected activity category (e.g., Casual, Hiking, Professional).
*   Filters suggestions to provide a sensible outfit (e.g., one shirt, one pair of pants/shorts, prioritizing key items like jackets/coats).
*   Includes fallback logic to ensure essential items (shirt, pants/shorts, shoes) are always suggested if possible.
*   Displays results with item images, current conditions, and a 12-hour forecast summary.
*   Features a dynamic background image based on the current weather conditions.
*   Persists the last used ZIP code and category using PHP sessions for convenience.

## Technology Stack

*   **Backend:** PHP
*   **Frontend:** HTML and CSS
*   **NOAA/NWS Weather API** (api.weather.gov)
*   **Nominatim Geocoding API** (nominatim.openstreetmap.org)

## Data Sources & Terms

*   **Weather Data:** Provided by the [NOAA/NWS API](https://www.weather.gov/documentation/services-web-api). Requires adherence to their Terms of Service, including the use of a **valid User-Agent** identifying your application and contact information (see `config.php`).
*   **Geocoding Data:** Provided by [Nominatim](https://nominatim.org/) using [OpenStreetMap](https://www.openstreetmap.org/copyright) data. Requires attribution as per the [Nominatim Usage Policy](https://operations.osmfoundation.org/policies/nominatim/). This attribution is automatically included in the site footer when geocoding is successful.

**Full compliance with the terms of service for both APIs.**

## Setup

1.  Clone or download this repository to your web server.
    ```bash
    git clone https://github.com/timnetworks/whatJacket.git
    ```
2.  Ensure your web server (e.g., Apache, Nginx) is configured to run PHP and has the `php-curl` extension enabled.
3.  Place the project files (`index.php`, `config.php`, `style.css`, `img/` directory, etc.) in your web server's document root or a suitable subdirectory.
4.  **IMPORTANT:** Open `config.php` and **update the `API_USER_AGENT` constant** with your actual application name, version, and contact information (email or website) as required by the NOAA API terms.
    ```php
    // Example:
    define('API_USER_AGENT', 'whatJacketApp/1.0 (you@example.com; https://your-website.com)');
    ```
5.  Make sure the `img/` directory structure exists (`img/backgrounds/`, `img/icons/`) and contains the image files referenced in `config.php`. Check file permissions if images are not loading.
6.  Access the `index.php` file via your web browser.

## Configuration

Most application settings are controlled within `config.php`:

*   `API_USER_AGENT`: **Must** be set for NOAA API compliance.
*   `NOAA_API_BASE_URL`, `GEOCODING_API_BASE_URL`: API endpoints.
*   `LOGO_IMAGE_PATH`: Path to the header logo.
*   `CATEGORIES`: List of available activity categories.
*   `TEMP_SCALE_MIN_F`, `TEMP_SCALE_MAX_F`: Defines the 0-10 temperature scale mapping.
*   `UMBRELLA_MAX_WIND_MPH`, `WINDY_THRESHOLD_MPH`: Weather thresholds.
*   `FOOTER_LINKS`: Links displayed in the footer.
*   `FORECAST_BACKGROUNDS`: Mapping of weather conditions to background images.
*   `SIMPLE_CONDITION_DISPLAY`: User-friendly names for weather conditions.
*   `TYPE_TO_DISPLAY_GROUP_MAP`: Maps clothing item types to display sections.
*   `CLOTHING_ITEMS`: The core database of clothing items, their properties, conditions, and image paths. Review and customize this array to adjust suggestions. Ensure all referenced image paths (`img`, `img_fallback`) are correct relative to `index.php`.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

Consider using a standard open-source license like [MIT](https://opensource.org/licenses/MIT). (You should add a `LICENSE` file to the repository if you choose one).

## Acknowledgements

*   Thanks to the **NOAA/NWS** and **OpenStreetMap/Nominatim** projects for providing the essential data APIs.
*   Code generated with **Google Gemini 2.5 Pro** Preview 03-25
