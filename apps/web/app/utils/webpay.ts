/**
 * Sends the customer to Webpay: a plain form that posts `token_ws` to the
 * URL the API returned. Never an iframe (Transbank's requirement).
 */
export function postToWebpay(url: string, token: string): void {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = url;

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'token_ws';
    input.value = token;

    form.append(input);
    document.body.append(form);
    form.submit();
}
