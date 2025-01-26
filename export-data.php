<?php include 'assets/php/access.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
    <title>Export Data</title>
    <style>
        .small-datetime {
            width: 150px; /* Adjust the width as needed */
        }
        .actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px; /* Add some space between elements */
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <a href="dashboard.php">
                <img src="assets/img/italia_logo.png" alt="Italia Furniture Logo" class="logo">
            </a>
        </div>
        <div class="store-info">
            <h1>Export Data</h1>
            <div class="actions">
                <select id="locationDropdown" class="line-dropdown">
                    <option value="ATL1">ATL1</option>
                    <option value="ATL2">ATL2</option>
                    <option value="ATL3">ATL3</option>
                </select>
                <input type="datetime-local" id="startDateTime" class="line-input small-datetime">
                <input type="datetime-local" id="endDateTime" class="line-input small-datetime">
                <div>
                    <button class="action-button" >Submit</button>
                </div>
            </div>
        </div>
        <div class="item-table">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody id="exportTableBody">
                    <!-- Filtered table rows will be dynamically added here -->
                </tbody>
            </table>
        </div>
        <button class="action-button" onclick="exportToSpreadsheet()">Export to Spreadsheet</button>
        <div id="exportModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal()">&times;</span>
                <h2>Export Options</h2>
                <p>Select an option to export your data:</p>
                <button class="action-button" onclick="downloadCSV()">Download as CSV</button>
                <br>
                <button class="action-button" id="uploadToDrive">Export to Google Docs</button>
            </div>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; 2023 Italia Furniture</p>
    </footer>
    <script src="https://apis.google.com/js/api.js"></script>
    <script>
        function fetchLocations() {
            fetch('assets/php/get_locations.php')
                .then(response => response.json())
                .then(locations => {
                    const locationDropdown = document.getElementById('locationDropdown');
                    locationDropdown.innerHTML = ''; // Clear existing options

                    locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location;
                        option.textContent = location;
                        locationDropdown.appendChild(option);
                    });

                    // Set initial location and load data
                    if (locations.length > 0) {
                        loadFilteredData(locations[0]);
                    }
                })
                .catch(error => console.error('Error fetching locations:', error));
        }

        function filterData() {
            const locationId = document.getElementById('locationDropdown').value;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;

            loadFilteredData(locationId, startDateTime, endDateTime);
        }

        function loadFilteredData(locationId, startDateTime = '', endDateTime = '') {
            const params = new URLSearchParams({
                location_id: locationId,
                start_date: startDateTime,
                end_date: endDateTime
            });

            console.log('Filtering SKUs:', params.toString()); // Log the filter parameters
            
            fetch(`assets/php/get_skus.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('exportTableBody');
                    tableBody.innerHTML = ''; // Clear existing table data

                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${item.sku_code}</td><td>${item.quantity}</td><td>${item.date_added}</td>`;
                        tableBody.appendChild(row);
                    });
                    console.log('Filtered SKUs:', data);
                    
                })
                .catch(error => console.error('Error fetching filtered SKUs:', error));
        }

        // Auto-refresh every 60 seconds (60000 milliseconds)
        setInterval(() => {
            const locationId = document.getElementById('locationDropdown').value;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            loadFilteredData(locationId, startDateTime, endDateTime);
        }, 60000);

        function exportToSpreadsheet() {
            const modal = document.getElementById('exportModal');
            modal.style.display = 'block';
        }

        function closeModal() {
            const modal = document.getElementById('exportModal');
            modal.style.display = 'none';
        }

        function downloadCSV() {
            // Logic to download CSV
            const locationDropdown = document.getElementById('locationDropdown');
            const location = locationDropdown.options[locationDropdown.selectedIndex].text;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            const tableBody = document.getElementById('exportTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            let csvContent = `Location: ${location}\n`;
            csvContent += `From: ${startDateTime} - ${endDateTime}\n\n`;
            csvContent += 'SKU,Quantity,Date Added\n';

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                const row = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent
                ].join(',');
                csvContent += row + '\n';
            }

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `export_${location}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            closeModal();
        }

        // Google Drive API
        // Replace with your own Google API credentials
        const API_KEY = "YOUR_API_KEY"
        const CLIENT_ID = "CLIENT_ID"
        const SCOPES = "https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/drive.file"

        // Set the redirect URI to match what you've set in the Google Cloud Console
        const REDIRECT_URI = "YOUR_DOMAIN" // Change this to your actual origin

        let isGapiLoaded = false
        let isGisLoaded = false

        function loadGapiClient() {
        gapi.load("client", initGapiClient)
        }
l
        function initGapiClient() {
        gapi.client
            .init({
            apiKey: API_KEY,
            discoveryDocs: ["https://sheets.googleapis.com/$discovery/rest?version=v4"],
            })
            .then(() => {
            isGapiLoaded = true
            checkAuth()
            })
        }

        function loadGisClient() {
        const script = document.createElement("script")
        script.src = "https://accounts.google.com/gsi/client"
        script.onload = () => {
            isGisLoaded = true
            checkAuth()
        }
        document.head.appendChild(script)
        }

        function checkAuth() {
        if (isGapiLoaded && isGisLoaded) {
            initializeGoogleAuth()
        }
        }

        function initializeGoogleAuth() {
        const tokenClient = google.accounts.oauth2.initTokenClient({
            client_id: CLIENT_ID,
            scope: SCOPES,
            redirect_uri: REDIRECT_URI,
            callback: "", // Leave this empty
        })

        document.getElementById("uploadToDrive").addEventListener("click", () => {
            tokenClient.callback = async (resp) => {
            if (resp.error !== undefined) {
                throw resp
            }
            await uploadTableData()
            }

            if (gapi.client.getToken() === null) {
            tokenClient.requestAccessToken({ prompt: "consent" })
            } else {
            tokenClient.requestAccessToken({ prompt: "" })
            }
        })
        }

        async function uploadTableData() {
        const locationDropdown = document.getElementById('locationDropdown');
        const location = locationDropdown.options[locationDropdown.selectedIndex].text;
        const sheetName = `export_${location}_${new Date().toISOString().slice(0, 10)}`
        const tableData = getTableData()

        try {
            const spreadsheet = await createSpreadsheet(sheetName)
            await updateSpreadsheetData(spreadsheet.spreadsheetId, tableData)
            closeModal();
            console.log("Table data uploaded successfully!")
        } catch (error) {
            console.error("Error uploading table data:", error)
            console.log("Error uploading table data. Please check the console for details.")
        }
        }
        
        function getTableData() {
            const locationDropdown = document.getElementById("locationDropdown")
            const location = locationDropdown.options[locationDropdown.selectedIndex].text
            const startDateTime = document.getElementById("startDateTime").value
            const endDateTime = document.getElementById("endDateTime").value
            const table = document.getElementById("exportTableBody")

            const data = [
                ["Location:", location],
                ["Start Date:", startDateTime, "End Date:", endDateTime],
                [], // Empty row for spacing 
                ['SKU', 'Quantity', 'Date Added'], 
            ]

            for (const row of table.rows) {
                const rowData = []
                for (const cell of row.cells) {
                rowData.push(cell.textContent)
                }
                data.push(rowData)
            }
            return data
        }

        async function createSpreadsheet(title) {
        const response = await gapi.client.sheets.spreadsheets.create({
            properties: {
            title: title,
            },
        })
        return response.result
        }

        async function updateSpreadsheetData(spreadsheetId, values) {
        await gapi.client.sheets.spreadsheets.values.update({
            spreadsheetId: spreadsheetId,
            range: "A1",
            valueInputOption: "RAW",
            resource: { values: values },
        })
        }

        // Load the Google API clients
        loadGapiClient()
        loadGisClient()
     
        // Fetch locations on page load
        document.addEventListener('DOMContentLoaded', fetchLocations);
    </script>
</body>
</html>

