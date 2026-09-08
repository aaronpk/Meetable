/**
 * Minimal browser client for the endpoints registered by laravel/passkeys.
 *
 * This is a hand-rolled stand-in for the @laravel/passkeys npm package, since
 * Meetable serves its Javascript directly rather than through a build step.
 */
class Passkeys {

    static #routes = {
        registerOptions: "/user/passkeys/options",
        register: "/user/passkeys",
        loginOptions: "/passkeys/login/options",
        login: "/passkeys/login",
    }

    static isSupported() {
        return typeof window.PublicKeyCredential !== "undefined"
            && typeof navigator.credentials?.create === "function"
    }

    /**
     * Registers a new passkey for the user who is already signed in.
     */
    static async register(name) {
        const {options} = await Passkeys.#request("GET", Passkeys.#routes.registerOptions)

        const credential = await navigator.credentials.create({
            publicKey: Passkeys.#decodeCreationOptions(options),
        })

        return Passkeys.#request("POST", Passkeys.#routes.register, {
            name: name || "Passkey",
            credential: Passkeys.#encodeCredential(credential),
        })
    }

    /**
     * Signs a user in with a passkey already known to their device.
     */
    static async login(remember) {
        const {options} = await Passkeys.#request("GET", Passkeys.#routes.loginOptions)

        const credential = await navigator.credentials.get({
            publicKey: Passkeys.#decodeRequestOptions(options),
        })

        return Passkeys.#request("POST", Passkeys.#routes.login, {
            remember: Boolean(remember),
            credential: Passkeys.#encodeCredential(credential),
        })
    }

    static async #request(method, url, body) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content

        const response = await fetch(url, {
            method: method,
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...(token ? {"X-CSRF-TOKEN": token} : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        })

        const json = await response.json().catch(() => null)

        if(!response.ok) {
            throw new Error(json?.message || ("Request to " + url + " failed with status " + response.status))
        }

        return json || {}
    }

    /**
     * The server sends binary values as base64url text, and the browser wants
     * ArrayBuffers, so the relevant fields are converted on the way in.
     */
    static #decodeCreationOptions(options) {
        return {
            ...options,
            challenge: Passkeys.#decode(options.challenge),
            user: {...options.user, id: Passkeys.#decode(options.user.id)},
            excludeCredentials: (options.excludeCredentials || []).map(c => Passkeys.#decodeDescriptor(c)),
        }
    }

    static #decodeRequestOptions(options) {
        return {
            ...options,
            challenge: Passkeys.#decode(options.challenge),
            allowCredentials: (options.allowCredentials || []).map(c => Passkeys.#decodeDescriptor(c)),
        }
    }

    static #decodeDescriptor(descriptor) {
        return {...descriptor, id: Passkeys.#decode(descriptor.id)}
    }

    /**
     * Shapes the browser's credential the way the server's request rules expect.
     */
    static #encodeCredential(credential) {
        const response = credential.response
        const encoded = {
            id: credential.id,
            type: credential.type,
            rawId: Passkeys.#encode(credential.rawId),
            response: {
                clientDataJSON: Passkeys.#encode(response.clientDataJSON),
            },
        }

        if(response.attestationObject) {
            encoded.response.attestationObject = Passkeys.#encode(response.attestationObject)
        }

        if(response.authenticatorData) {
            encoded.response.authenticatorData = Passkeys.#encode(response.authenticatorData)
            encoded.response.signature = Passkeys.#encode(response.signature)
            encoded.response.userHandle = response.userHandle ? Passkeys.#encode(response.userHandle) : null
        }

        return encoded
    }

    static #decode(value) {
        const padded = value.replace(/-/g, "+").replace(/_/g, "/")
        const binary = atob(padded.padEnd(padded.length + (4 - padded.length % 4) % 4, "="))

        return Uint8Array.from(binary, character => character.charCodeAt(0)).buffer
    }

    static #encode(buffer) {
        let binary = ""

        for(const byte of new Uint8Array(buffer)) {
            binary += String.fromCharCode(byte)
        }

        return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "")
    }

}
