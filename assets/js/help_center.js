    const cities = ["Hà Nội", "Hồ Chí Minh", "Đà Nẵng", "Cần Thơ", "Huế", "Nha Trang"];
    const searchInput = document.getElementById("searchInput");
    const suggestions = document.getElementById("suggestions");

    function showSuggestions() {
        suggestions.innerHTML = "";
        const value = searchInput.value.toLowerCase();
        const filteredCities = value ? cities.filter(city => city.toLowerCase().includes(value)) : cities;
        filteredCities.forEach(city => {
            const div = document.createElement("div");
            div.textContent = city;
            div.addEventListener("click", function() {
                searchInput.value = city;
                suggestions.style.display = "none";
            });
            suggestions.appendChild(div);
        });
        suggestions.style.display = "block";
    }

    searchInput.addEventListener("input", showSuggestions);

    document.addEventListener("click", function(e) {
        if (!searchInput.contains(e.target)) {
            suggestions.style.display = "none";
        }
    });

    // check định dạng sđt
    const phoneInput = document.querySelector("input[type='tel']");
    const phoneError = document.createElement("div");
    phoneError.style.color = "red";
    phoneError.style.display = "none";
    phoneInput.parentElement.appendChild(phoneError);
    phoneInput.addEventListener("input", function() {
        const phonePattern = /^[0-9]{10,11}$/;
        if (!phonePattern.test(phoneInput.value)) {
            phoneError.textContent = "Số điện thoại phải từ 10-11 chữ số.";
            phoneError.style.display = "block";
        } else {
            phoneError.style.display = "none";
        }
    });

    // check định dạng email
    const emailInput = document.querySelector("input[type='email']");
    const emailError = document.createElement("div");
    emailError.style.color = "red";
    emailError.style.display = "none";
    emailInput.parentElement.appendChild(emailError);
    emailInput.addEventListener("input", function() {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value)) {
            emailError.textContent = "Email không hợp lệ.";
            emailError.style.display = "block";
        } else {
            emailError.style.display = "none";
        }
    });

    // upload file or images
    const dropArea = document.querySelector(".drag-area");
    const input = dropArea.querySelector("input");
    let file;

    dropArea.querySelector("button").onclick = () => {
        input.click();
    };

    input.addEventListener("change", function() {
        file = this.files[0];
        dropArea.classList.add("active");
        showFile();
    });

    dropArea.addEventListener("dragover", (event) => {
        event.preventDefault();
        dropArea.classList.add("active");
    });

    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("active");
    });

    dropArea.addEventListener("drop", (event) => {
        event.preventDefault();
        file = event.dataTransfer.files[0];
        dropArea.classList.add("active");
        showFile();
    });

    function showFile() {
        let fileType = file.type;
        let validExtensions = ["image/jpeg", "image/gif", "image/png", "application/pdf", "video/mp4", "video/heic", "video/hevc"];
        if (validExtensions.includes(fileType)) {
            let fileReader = new FileReader();
            fileReader.onload = () => {
                let fileURL = fileReader.result;
                let imgTag = `<img src="${fileURL}" alt="uploaded file">`;
                dropArea.innerHTML = imgTag;
            };
            fileReader.readAsDataURL(file);
        } else {
            alert("This file type is not supported!");
            dropArea.classList.remove("active");
        }
    }

    // giới hạn ký tự ở decription

    const textarea = document.querySelector(".problem-description textarea");
    const charCount = document.querySelector(".problem-description .char-count");
    textarea.addEventListener("input", function() {
        const maxLength = this.maxLength;
        const currentLength = this.value.length;
        charCount.textContent = `${currentLength} of ${maxLength} max characters`;
    });