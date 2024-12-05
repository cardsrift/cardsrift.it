import heroSlider from '../global-components/hero/heroSlider';
import heroSliderV2 from '../global-components/hero_v2/heroSlider-v2';
import bgImageSlider from '../global-components/bg_image_slider/bgImageSlider';
import bigActiveSlider from '../global-components/big_active_slider/bigActiveSlider';
import doubleSlider from '../global-components/double_slider/doubleSlider';
import video from '../global-components/video_vimeo_embed/video';

const globalComponents = () => {
	heroSlider();
	heroSliderV2();
	bgImageSlider();
	bigActiveSlider();
	doubleSlider();
	video();
};

export default globalComponents;
