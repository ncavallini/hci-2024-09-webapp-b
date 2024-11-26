const LOCAL_STORAGE_AB_KEY = "ABVersion";
const AB_TEST_VERSION_A = "A";
const AB_TEST_VERSION_B = "B";

function initABTest() {
    if(window.localStorage.getItem(LOCAL_STORAGE_AB_KEY) === null) {
        window.localStorage.setItem(LOCAL_STORAGE_AB_KEY, AB_TEST_VERSION_A);
    }
}

function getABTestVersion() {
    return window.localStorage.getItem(LOCAL_STORAGE_AB_KEY);
}

initABTest();