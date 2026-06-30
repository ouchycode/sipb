import { ref, onMounted, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";

export function usePageLoading(initialValue = false) {
    const pageLoading = ref(initialValue);

    let removeStartListener = null;
    let removeFinishListener = null;

    onMounted(() => {
        pageLoading.value = false;
        removeStartListener = router.on("start", () => {
            pageLoading.value = true;
        });
        removeFinishListener = router.on("finish", () => {
            pageLoading.value = false;
        });
    });

    onBeforeUnmount(() => {
        removeStartListener?.();
        removeFinishListener?.();
    });

    return { pageLoading };
}
