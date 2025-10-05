// three.js
// --------------------------------------------------------
// الاستيرادات
// --------------------------------------------------------
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { EXRLoader } from 'three/examples/jsm/loaders/EXRLoader.js';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

// --------------------------------------------------------
// المتغيرات الأساسية
// --------------------------------------------------------
let scene, camera, renderer, controls;
let currentPlanet = null;
const loader = new GLTFLoader();

const MODEL_PATHS = {
  rocky: '/assets/rock_surface_4k.blend/rock_surface_4k.gltf',
  sand: '/assets/sand_surface_4k.blend/sand_plane_4k.gltf',
  water: '/assets/water_surface_4k.blend/water_plane.gltf',
  default: '/assets/rock_surface_4k.blend/rock_surface_4k.gltf',
};

// --------------------------------------------------------
// التهيئة
// --------------------------------------------------------
function init() {
  // مشهد
  scene = new THREE.Scene();

  // كاميرا
  camera = new THREE.PerspectiveCamera(
    60,
    window.innerWidth / window.innerHeight,
    0.1,
    1000
  );
  camera.position.set(0, 2, 5);

  // رندر
  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(window.devicePixelRatio);
  document.body.appendChild(renderer.domElement);

  // تحكم
  controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.05;
  controls.target.set(0, 0, 0);
  // ⭐ أضف هذين السطرين هنا
controls.minDistance = 2;
controls.maxDistance = 3;
  controls.update();

  // إضاءة
  const ambient = new THREE.AmbientLight(0xffffff, 0.7);
  scene.add(ambient);

  const dirLight = new THREE.DirectionalLight(0xffffff, 2);
  dirLight.position.set(5, 10, 5);
  scene.add(dirLight);

  // HDRI
  setupHDRI();

  // لوب
  animate();

  // ريسايز
  window.addEventListener('resize', onWindowResize);
}

// --------------------------------------------------------
// تحميل HDRI
// --------------------------------------------------------
function setupHDRI() {
  new EXRLoader()
    .setPath('/assets/environments/')
    .load(
      'NightSkyHDRI001_2K-HDR.exr',
      function (texture) {
        texture.mapping = THREE.EquirectangularReflectionMapping;
        scene.environment = texture;
        scene.background = texture;
        console.log('✅ HDRI تم تحميله بنجاح');
      },
      undefined,
      (error) => {
        console.error('❌ فشل تحميل HDRI:', error);
        scene.background = new THREE.Color(0x000000);
      }
    );
}

// --------------------------------------------------------
// تحميل الكوكب
// --------------------------------------------------------
export function loadTargetModel(planetData) {
  if (!planetData) return;

  const planetType = (planetData.physicalType || 'default').toLowerCase();
  const path = MODEL_PATHS[planetType] || MODEL_PATHS['default'];
  const radius = planetData.planetRadius || 1.0;

  // إزالة القديم
  if (currentPlanet) {
    scene.remove(currentPlanet);
    currentPlanet = null;
  }

  // تحميل الجديد
  loader.load(
    path,
    (gltf) => {
      currentPlanet = gltf.scene;

      // مقياس
    //   const scaleFactor = 0.5 + radius * 0.15;
      currentPlanet.scale.set(1, 1, 1);
      currentPlanet.position.set(0, 0, 0);

      // تحسين الإضاءة
      currentPlanet.traverse((child) => {
        if (child.isMesh && child.material.isMeshStandardMaterial) {
          child.material.envMap = scene.environment;
          child.material.envMapIntensity = 1.0;
          child.material.needsUpdate = true;
        }
      });

      // إضافة للمشهد
      scene.add(currentPlanet);

      // الكاميرا
      camera.position.set(3,3,3);
      controls.target.copy(currentPlanet.position);
      controls.update();

      console.log(`✅ تم تحميل النموذج: ${path}`);
    },
    (xhr) => {
      console.log((xhr.loaded / xhr.total) * 100 + '% loaded of 3D model');
    },
    (error) => {
      console.error('❌ فشل تحميل النموذج:', error);
    }
  );
}

// --------------------------------------------------------
// لوب العرض
// --------------------------------------------------------
function animate() {
  requestAnimationFrame(animate);

  // دوران تلقائي للكوكب
  if (currentPlanet) {
    currentPlanet.rotation.y += 0.002;
  }

  controls.update();
  renderer.render(scene, camera);
}

// --------------------------------------------------------
// ريسايز
// --------------------------------------------------------
function onWindowResize() {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
}

// --------------------------------------------------------
// بدء التشغيل
// --------------------------------------------------------
document.addEventListener('DOMContentLoaded', init);
