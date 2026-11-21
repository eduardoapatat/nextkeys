/** Pages for a signed in customer: anyone else goes to sign in first. */
export default defineNuxtRouteMiddleware((to) => {
    if (!useCustomer().value) {
        return navigateTo({
            path: '/cuenta/ingresar',
            query: { redirect: to.fullPath },
        });
    }
});
