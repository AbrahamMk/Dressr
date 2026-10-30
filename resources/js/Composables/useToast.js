import { reactive } from 'vue';

const toasts = reactive([]);

let nextId = 0;

export function useToast() {
    const dismiss = (id) => {
        const index = toasts.findIndex((toast) => toast.id === id);

        if (index !== -1) {
            toasts.splice(index, 1);
        }
    };

    const push = (message, variant = 'success', timeout = 4000) => {
        const id = ++nextId;

        toasts.push({ id, message, variant });

        if (timeout) {
            setTimeout(() => dismiss(id), timeout);
        }

        return id;
    };

    return { toasts, push, dismiss };
}
