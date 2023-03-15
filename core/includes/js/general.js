// add class to body if using Hello Elementor theme for css fixes
const helloElementor = document.querySelector("#hello-elementor-css");
const body = document.querySelector("body");

if (helloElementor) {
    body.classList.add("hello-elementor");
};


// add class to body if using 2021 theme
const twentyTwentyOne = document.querySelector("#twenty-twenty-one-style-css");
if (twentyTwentyOne) {
    body.classList.add("twentytwentyone-theme");
};

// add class to body if using 2022 theme
const twentyTwentyTwo = document.querySelector("#twenty-twenty-two-style-css");
if (twentyTwentyTwo) {
    body.classList.add("twentytwentytwo-theme");
};
    