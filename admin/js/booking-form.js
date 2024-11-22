jQuery(document).ready(function($) {
    
    // List of input field IDs to check
const globalFields = ['full-name', 'email', 'phone', 'budget-type', 'total-budget', 'payment-prefrence', 'quote-type'];

var flightonlyfields = ['fof_departure_date', 'fof_arrival_date', 'fof_passenger-name-1', 'fof_passenger-dob-1'];
const vacationfields = ['vpf_vacation-destination', 'vpf_vacation_start_date', 'vpf_vacation_end_date', 'payment-prefrence-vacation'];
const cruisefields =['cpf_departure_date', 'cpf_return_date'];

const accommodationfields = ['aof_check_in_date', 'aof_check_out_date'];
const fahcpfields = ['fhcp_departure_date', 'fhcp_arrival_date'];
const europefields = ['eu_departure_date', 'eu_arrival_date'];
const adventurefields = ['adventure_departure_date', 'adventure_arrival_date'];
const rentalfields = ['rental_departure_date', 'rental_arrival_date'];
const insurancefields = ['insurance_start_date', 'insurance_end_date'];
function checkEmptyFields(inputIds) {
    console.log(inputIds);
  let allFilled = true;
  let firstEmptyField = null;

  inputIds.forEach(id => {
    const inputElement = document.getElementById(id);

    if (inputElement && inputElement.value.trim() === '') {
      // Apply red border to the empty field
      inputElement.style.border = "2px solid red";
      allFilled = false;

      // Store the first empty field for scrolling
      if (!firstEmptyField) {
        firstEmptyField = inputElement;
      }
    } else if (inputElement) {
      // Reset border if it has content
      inputElement.style.border = "";
    }
        // Add an onchange event listener to remove the red border when filled
    inputElement.addEventListener("input", function() {
      if (inputElement.value.trim() !== "") {
        inputElement.style.border = ""; // Remove red border
      }
    });
  });

  if (!allFilled && firstEmptyField) {
    // Scroll to the first empty field and show alert
    firstEmptyField.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  return allFilled;
}

function getchildagefields(count_field_id, age_field_id)
{
    let number_children = parseInt(document.getElementById(count_field_id).value) || 0; 
        let child_field = [];
        if(number_children > 0) 
        {
            for (let i = 1; i <= number_children; i++) 
            {
              child_field.push(age_field_id + i);

            }
        }
        return child_field;
}

    $('#booking-form').on('submit', function(e) {
         e.preventDefault();

    if (checkEmptyFields(globalFields))
  {
        const budgetSelect = document.getElementById("quote-type");
        const selectedValue = budgetSelect.value;
        var state = false;
            switch (selectedValue) {
      case "Flight Only":
        let passenger_count =  parseInt(document.getElementById('fof_passenger_count').value) + 1;
        let passengerfields = []
        if(passenger_count > 2)
        {
            for (let i = 1; i < passenger_count; i++) {
              let name_id = 'fof_passenger-name-' + i;
              let dob_id = 'fof_passenger-dob-' + i;
              if(document.getElementById(name_id))
              {
                   passengerfields.push(name_id);
              }
            if(document.getElementById(dob_id))
              {
                   passengerfields.push(dob_id);
              }

            }
        }
        state =  checkEmptyFields(flightonlyfields.concat(passengerfields));
        console.log(passenger_count);
        break;
      case "Vacation Packages":
        state =  checkEmptyFields(vacationfields);
        break;
      case "Cruise Package":
        state =  checkEmptyFields(cruisefields);
        break;
      case "Accommodations Only":
        state =  checkEmptyFields(accommodationfields.concat(getchildagefields('aof_number-of-children', 'child-age-')));

        break;
      case "Flight & Hotel City Packages":
        state =  checkEmptyFields(fahcpfields.concat(getchildagefields('fhcp-number-of-children', 'fhcp-child-age-')));
        break;
      case "Europe Packages":
        state =  checkEmptyFields(europefields.concat(getchildagefields('eu-number-of-children', 'eu-child-age-')));
        break;
      case "Adventure Group Travel":
          const element = document.getElementById("adventure_passenger_count");
           let passengerCount =  0;
            let passenger_fields = [];
            if (element)
            {
              passengerCount =  parseInt( document.getElementById("adventure_passenger_count").value);
            }
        if(passengerCount > 0)
        {
            for (let i = 1; i <= passengerCount; i++)
            {

              let nameid = 'adventure-passenger-name-' + i;
              let dobid = 'adventure-passenger-dob-' + i;
              if(document.getElementById(nameid))
              {
                   passenger_fields.push(nameid);
              }
            if(document.getElementById(dobid))
              {
                   passenger_fields.push(dobid);
              }

            }
        }
        state =  checkEmptyFields(adventurefields.concat(passenger_fields));
        break;
      case "Car Rental Only":
        state =  checkEmptyFields(rentalfields);
        break;
      case "Travel Insurance Only":
        state =  checkEmptyFields(insurancefields.concat(getchildagefields('insurance-number-of-children', 'insurance-child-age-')));
        break;
      default:
        console.log(selectedValue);
    }

    if(state) {

        var recaptchaResponse = grecaptcha.getResponse();
        console.log("here we are");
        var formData = $(this).serialize();
        var submitBtn = $('#quote-submit-btn');
        var spinner = submitBtn.find('.spinner-border');
        var buttonText = submitBtn.find('.button-text');
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        buttonText.text('Submitting...');


        $.ajax({
            type: 'POST',
            url: bookingForm.ajax_url,
            data: {
                action: 'save_booking_form',
                nonce: bookingForm.nonce,
                recaptcha_response: recaptchaResponse,
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    // Success scenario
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message
                    }).then(function() {
                        window.location.href = bookingForm.home_url;
                    });
                } else {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Some error occur'
                }).then(function() {
                    location.reload();
                });

            },
            complete: function() {
                // Re-enable the button and hide spinner
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
                buttonText.text('Submit Quote');
            }
        });
    }



  }

    });

    $('#cpf_flight-quote-required').change(function() {
        if ($(this).val() === 'yes') {
            $('#cpf_departure-city-container').show(); 
        } else {
            $('#cpf_departure-city-container').hide(); 
        }
    });
    $('#adventure-flight-requirement').change(function() {
        if ($(this).val() === 'yes') {
            $('#adventuredeparture-city-container').show(); 
            $('#adventurearrival-city-container').show(); 
        } else {
            $('#adventuredeparture-city-container').hide(); 
            $('#adventurearrival-city-container').hide(); 
        }
    });
    $('#quote-type').change(function() {
        var selectedType = $(this).val();
        $('.conditional-group').hide(); 

        switch (selectedType) {
            case 'Flight Only':
                $('#flight-only-fields').show();
                break;
            case 'Vacation Packages':
                $('#vacation-packages-fields').show();
                $('.payment-prefrence-div').hide();
                break;
            case 'Cruise Package':
                $('#cruise-package-fields').show();
        
                break;
            case 'Accommodations Only':
                $('#accommodations-only-fields').show();
                    
                break;
            case 'Flight & Hotel City Packages':
                $('#flight-hotel-city-packages-fields').show();
                
                break;
            case 'Europe Packages':
                $('#europe-packages-fields').show();
                 
                 
                break;
            case 'Adventure Group Travel':
                $('#adventure-group-travel-fields').show();
                 
                break;
            case 'Car Rental Only':
                $('#car-rental-only-fields').show();
                 $('.budget-total').hide();
                 $('.budget-type').hide();
                  
                break;
            case 'Travel Insurance Only':
                $('#travel-insurance-only-fields').show();
                $('.budget-total').hide();
                 $('.budget-type').hide();
                break;
        }
    });
    document.getElementById('add-passenger').addEventListener('click', function() {
        var container = document.getElementById('passengers-container');
        var passengerCount =  parseInt(document.getElementById('fof_passenger_count').value) + 1;
        var newPassenger = `
            <div class="card mb-4 passenger-group">  <!-- Use Bootstrap card for styling -->
                <div class="card-header">
                    <h5>Passenger ${passengerCount} Details</h5> <!-- Header for each passenger -->
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="passenger-name-${passengerCount}">Full Name</label>
                        <input type="text" class="form-control" id="fof_passenger-name-${passengerCount}" name="fof_passenger_names_${passengerCount}" placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label for="passenger-dob-${passengerCount}">DOB</label>
                        <input type="date" class="form-control" id="fof_passenger-dob-${passengerCount}" name="fof_passenger_dobs_${passengerCount}">
                    </div>
                    <button type="button" class="btn btn-danger" onclick="removePassengergroup(this)">Remove Passenger</button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', newPassenger);
        document.getElementById('fof_passenger_count').value = passengerCount;
    });
    const airportUrl = bookingForm.pluginUrl + '/airports.json';

    
    $('.airport-select').select2({
        placeholder: 'Please type an airport',
        allowClear: true,
        tags: true,
        minimumInputLength: 1,
        ajax: {
            transport: function(params, success, failure) {
                fetch(airportUrl)
                    .then(response => response.json())
                    .then(data => {
                        const query = params.data.q.toLowerCase(); 
                        const suggestions = data.filter(airport => {
                            return airport.city.toLowerCase().includes(query) || 
                                   airport.name.toLowerCase().includes(query) ||
                                   airport.code.toLowerCase().includes(query);
                        });

                        
                        const results = suggestions.map(airport => ({
                            id: airport.name, 
                            text: `${airport.name} (${airport.city})` 
                        }));

                        success({ results: results }); 
                    })
                    .catch(error => {
                        console.error('Error fetching airport data:', error);
                        failure(error); 
                    });
            },
            delay: 250,
            cache: true
        },
        templateResult: function(data) {
            return data.text; 
        },
        templateSelection: function(data) {
            return data.text || 'Please type an airport'; 
        }
    });


    $('#adventure_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#adventure_arrival_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#adventure_arrival_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#adventure_arrival_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    $('#insurance_start_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#insurance_end_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#insurance_end_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#insurance_end_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    $('#rental_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#rental_arrival_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#rental_arrival_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#rental_arrival_date').val(departureDate.toISOString().split('T')[0]);
        }
    });



    $('#fof_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#fof_arrival_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#fof_arrival_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#fof_arrival_date').val(departureDate.toISOString().split('T')[0]);
        }
    });
    $('#vpf_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#vpf_arrival_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#vpf_arrival_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#vpf_arrival_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    $('#vpf_vacation_start_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#vpf_vacation_end_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#vpf_vacation_end_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#vpf_vacation_end_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    $('#aof_check_in_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#aof_check_out_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#aof_check_out_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#aof_check_out_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    $('#fhcp_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#fhcp_arrival_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#fhcp_arrival_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#fhcp_arrival_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    $('#eu_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today",
        disableMobile: true, 
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#eu_arrival_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#eu_arrival_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#eu_arrival_date').val(departureDate.toISOString().split('T')[0]);
        }
    });


    $('#cpf_departure_date').flatpickr({
        dateFormat: "Y-m-d",
        minDate: "today", 
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            const arrivalDate = new Date($('#cpf_return_date').val());
            const departureDate = toLocalDate(dateStr);

            $('#cpf_return_date').flatpickr({
                minDate: departureDate, 
                dateFormat: "Y-m-d",
                disableMobile: true,
            });
            $('#cpf_return_date').val(departureDate.toISOString().split('T')[0]);
        }
    });

    const multiSelectWithoutCtrl = (elemSelector) => {
        let options = [].slice.call(document.querySelectorAll(`${elemSelector} option`));
        options.forEach(function (element) {
            element.addEventListener("mousedown", function (e) {
                e.preventDefault();
                element.parentElement.focus();
                this.selected = !this.selected;
                return false;
            }, false);
        });
    }
    multiSelectWithoutCtrl('#cpf_cruise-type');
    multiSelectWithoutCtrl('#cpf_preferred-cruise-line');

    multiSelectWithoutCtrl('#eu-flight-options');
    multiSelectWithoutCtrl('#fof_flight-options');
    multiSelectWithoutCtrl('#vpf_flight-options');
    multiSelectWithoutCtrl('#fhcp-flight-options');
    multiSelectWithoutCtrl('#vpf_resort-preferences');
    multiSelectWithoutCtrl('#fhcp-accommodation-preferences');
    multiSelectWithoutCtrl('#aof_hotel-preferences');
    multiSelectWithoutCtrl('#eu-accommodation-preferences');
    multiSelectWithoutCtrl('#adventure-company');
    multiSelectWithoutCtrl('#rental_car_type_preferences');
    
    

});
function toLocalDate(dateStr) {
    var temp_date =  new Date(dateStr + 'T00:00:00');
    temp_date.setDate(temp_date.getDate() + 1);
   return temp_date;
}

function populateChildAgesaof() {
    const numberOfChildren = parseInt(document.getElementById('aof_number-of-children').value) || 0; 
    const childAgesContainer = document.getElementById('child-ages-containeraof');
    const childAgesDiv = document.getElementById('child-ages-aof');
    childAgesDiv.innerHTML = ''; 

    if (numberOfChildren > 0) {
        childAgesContainer.style.display = 'block';

        for (let i = 1; i <= numberOfChildren; i++) {
            const childAgeField = `
                <div class="form-group">
                    <label for="child-age-${i}">Age of Child ${i}</label>
                    <input type="number" class="form-control" id="child-age-${i}" name="aof_child_ages_${i}" min="0" placeholder="Enter age of child ${i}">
                </div>
            `;
            childAgesDiv.insertAdjacentHTML('beforeend', childAgeField);
        }
    } else {
        childAgesContainer.style.display = 'none';
    }
}







function populateChildAgesvpf() {
    const numberOfChildren = parseInt(document.getElementById('vpf_number-of-children').value) || 0; 
    const childAgesContainer = document.getElementById('vpf-child-ages-container');
    const childAgesDiv = document.getElementById('vpf-child-ages');

    
    childAgesDiv.innerHTML = '';

    if (numberOfChildren > 0) {
        childAgesContainer.style.display = 'block';

        for (let i = 1; i <= numberOfChildren; i++) {
            const childAgeField = `
                <div class="form-group">
                    <label for="child-age-${i}">Age of Child ${i}</label>
                    <input type="number" class="form-control" id="vpf-child-age-${i}" name="vpf_child_ages_${i}" min="0" placeholder="Enter age of child ${i}">
                </div>
            `;
            childAgesDiv.insertAdjacentHTML('beforeend', childAgeField);
        }
    } else {
        childAgesContainer.style.display = 'none';
    }
}



function populateChildAges() {
    const numberOfChildren = document.getElementById('number-of-children').value;
    const childAgesContainer = document.getElementById('child-ages-container');
    childAgesContainer.innerHTML = ''; 

    for (let i = 1; i <= numberOfChildren; i++) {
        const childAgeField = `
            <div class="form-group">
                <label for="child-age-${i}">Age of Child ${i}</label>
                <input type="number" class="form-control" id="child-age-${i}" name="child_ages_${i}" min="0" placeholder="Enter age of child ${i}" >
            </div>
        `;
        childAgesContainer.insertAdjacentHTML('beforeend', childAgeField);
    }
}
function populateChildAgesForFHCP() {
    const numberOfChildren = document.getElementById('fhcp-number-of-children').value;
    const childAgesContainer = document.getElementById('fhcp-child-ages-container');
    const childAges = document.getElementById('fhcp-child-ages');

    childAges.innerHTML = ''; 

    if (numberOfChildren > 0) {
        childAgesContainer.style.display = 'block'; 
        for (let i = 0; i < numberOfChildren; i++) {
            childAges.innerHTML += `
                <div class="form-group">
                    <label for="fhcp-child-age-${i + 1}">Age of Child ${i + 1}</label>
                    <input type="number" class="form-control" id="fhcp-child-age-${i + 1}" name="fhcp_child_ages_${i + 1}" min="0" placeholder="Enter age of child">
                </div>
            `;
        }
    } else {
        childAgesContainer.style.display = 'none'; 
    }
}
function populateChildAgesForEU() {
    const numberOfChildren = document.getElementById('eu-number-of-children').value;
    const childAgesContainer = document.getElementById('eu-child-ages-container');
    const childAges = document.getElementById('eu-child-ages');

    childAges.innerHTML = ''; 

    if (numberOfChildren > 0) {
        childAgesContainer.style.display = 'block'; 
        for (let i = 0; i < numberOfChildren; i++) {
            childAges.innerHTML += `
                <div class="form-group">
                    <label for="eu-child-age-${i + 1}">Age of Child ${i + 1}</label>
                    <input type="number" class="form-control" id="eu-child-age-${i + 1}" name="eu_child_ages_${i + 1}" min="0" placeholder="Enter age of child">
                </div>
            `;
        }
    } else {
        childAgesContainer.style.display = 'none'; 
    }
}

function populateChildAgesForInsurance() {
    const numberOfChildren = document.getElementById('insurance-number-of-children').value;
    const childAgesContainer = document.getElementById('insurance-child-ages-container');
    const childAges = document.getElementById('insurance-child-ages');

    childAges.innerHTML = ''; 

    if (numberOfChildren > 0) {
        childAgesContainer.style.display = 'block'; 
        for (let i = 0; i < numberOfChildren; i++) {
            childAges.innerHTML += `
                <div class="form-group">
                    <label for="insurance-child-age-${i + 1}">Age of Child ${i + 1}</label>
                    <input type="number" class="form-control" id="insurance-child-age-${i + 1}" name="insurance_child_ages_${i + 1}" min="0" placeholder="Enter age of child">
                </div>
            `;
        }
    } else {
        childAgesContainer.style.display = 'none'; 
    }
}

let passengerCount = 0;

function addPassenger() {
    
    if (passengerCount < 20) {
        passengerCount++;
        const container = document.getElementById('adventure-passenger-info-container');
        const passengerDiv = document.createElement('div');
        passengerDiv.classList.add('passenger-info');
        passengerDiv.setAttribute('id', `passenger-${passengerCount}`); 
         document.getElementById("adventure_passenger_count").value = passengerCount;
        passengerDiv.innerHTML = `
            <div class="card mb-4">  <!-- Use Bootstrap card for styling -->
                <div class="card-header">
                    <h5>Passenger ${passengerCount} Details</h5> <!-- Header for each passenger -->
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="adventure-passenger-name-${passengerCount}">Full Name of Passenger ${passengerCount}</label>
                        <input type="text" class="form-control" id="adventure-passenger-name-${passengerCount}" name="adventure_passenger_name_${passengerCount}" placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label for="adventure-passenger-dob-${passengerCount}">Date of Birth</label>
                        <input type="date" class="form-control" id="adventure-passenger-dob-${passengerCount}" name="adventure_passenger_dob_${passengerCount}">
                    </div>
                    <button type="button" class="btn btn-danger" onclick="removePassenger(${passengerCount})">Remove Passenger</button>
                </div>
            </div>
        `;
        container.appendChild(passengerDiv);
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Maximum of 20 passengers can be added.'
        }).then(function() {
            location.reload(); 
        });
    }
}

function removePassenger(count) {
    const passengerDiv = document.getElementById(`passenger-${count}`);
    if (passengerDiv) {
        passengerDiv.remove(); 
        passengerCount--; 
        document.getElementById("adventure_passenger_count").value = passengerCount;
    }
}

function removePassengergroup(button) {
    var passengerGroup = button.closest('.passenger-group');
    if (passengerGroup) {
        passengerGroup.remove(); 
        let temp= document.getElementById('fof_passenger_count').value;
        temp=temp-1;
         document.getElementById("fof_passenger_count").value = temp;
    }
}

