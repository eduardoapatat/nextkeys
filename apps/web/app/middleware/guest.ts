/** Sign in and registration: a signed in customer goes to their account. */
export default defineNuxtRouteMiddleware(() => {
    if (useCustomer().value) {
        return navigateTo('/cuenta');
    }
});
