Project descriptions 

Coding Assignment
Trip Builder
Outline
An airline has a name and is identified by an IATA Airline Code.
Ex: Air Canada (AC)
An airport is a location identified by an IATA Airport Code. It also has a name, a city, latitude and longitude coordinates, a timezone and a city code, the IATA Airport Code for a city, which may differ from an airport code in larger areas.
Ex: Pierre Elliott Trudeau International (YUL) belongs to the Montreal (YMQ) city code.
A flight is uniquely numbered for a referenced airline. For the sake of simplicity, a flight is priced for a single passenger (any gender, any type) in a neutral currency and is available every day of the week. It references a pair of airports for departure and arrival. It has departure and arrival times in the corresponding airport timezones.
Ex: AC301 from YUL to YVR departs at 7:35 AM (Montreal) and arrives at 10:05 AM (Vancouver).
Your Mission
Create a fully functional web application and web services to build and navigate trips for a single passenger using criteria such as departure locations, departure dates and arrival locations. Be mindful of timezones!
A trip references one or many flights with dates of departure. The price amounts to the total of the price of the referenced flights.
The following trip types MUST be supported:
● A one-way is a flight getting from A to B
● A round-trip is a pair of one-ways getting from A to B then from B to A.
A trip MUST depart after creation time at the earliest or 365 days after creation time at the latest.
Technical Requirements
● Server-side application(s) MUST be written in PHP ● The UI should preferably work as a no-refresh, Single Page Application. Use of a JS Framework is highly recommended. (React, Vue, Angular…)
● The resulting project MUST be version-controlled and hosted online
● Easy to follow instructions MUST be provided to provision an environment, install and run the application locally on a PC (Windows or Linux) and/or Mac FlightHub PHP Coding Assignment
How to Earn Extra Considerations
● Deploy the application online to ease the review process
● Scale beyond sample data (see below)
● Use data storage(s) provisioned within the environment
● Implement automated software tests
● Document Web Services
● Allow flights to be restricted to a preferred airline
● Sort trip listings
● Paginate trip listings
● Allow flights departing and/or arriving in the vicinity of requested locations
● Support open-jaw trips, a pair of one-ways getting from A to B then from C to A
● Support multi-city trips, one-ways (up to 5) from A to B, B to C, C to D, etc.
Sample Data
{
"airlines": [
{
"code": "AC",
"name": "Air Canada"
}
],
"airports": [
{
"code": "YUL",
"city_code": "YMQ",
"name": "Pierre Elliott Trudeau International",
"city": "Montreal",
"country_code": "CA",
"region_code": "QC",
"latitude": 45.457714,
"longitude": -73.749908,
"timezone": "America/Montreal"
},
{
"code": "YVR",
"city_code": "YVR",
"name": "Vancouver International",
"city": "Vancouver",
"country_code": "CA",
"region_code": "BC",
"latitude": 49.194698,
"longitude": -123.179192,
"timezone": "America/Vancouver"
}
],
"flights": [
{
"airline": "AC",
"number": "301",
"departure_airport": "YUL",
"departure_time": "07:35",
"arrival_airport": "YVR",
"arrival_time": "10:05",
"price": "273.23"
},
{
"airline": "AC",
"number": "302",
"departure_airport": "YVR",
"departure_time": "11:30",
"arrival_airport": "YUL",
"arrival_time": "19:11",
"price": "220.63"
}
]
}
