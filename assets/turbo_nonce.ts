/**
 * Browsers hide the nonce of scripts in a page served with a CSP header: the
 * attribute reads as "" while the real value stays in the `nonce` property.
 * Turbo caches pages by cloning the live DOM, then re-creates their scripts by
 * copying attributes, so a cached page shown as a preview (revisit, back
 * button) re-runs its scripts with an empty nonce and the CSP blocks them.
 * Writing the property back into the attribute lets Turbo copy the real nonce.
 */
export function restoreHiddenNonces(root: ParentNode): void {
    root.querySelectorAll<HTMLScriptElement>('script[nonce=""]').forEach((script) => {
        if (script.nonce) {
            script.setAttribute('nonce', script.nonce)
        }
    })
}
