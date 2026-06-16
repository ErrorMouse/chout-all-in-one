(function() {
   // --- Configuration ---
   const snowMax = 335; // Maximum number of snowflakes
   const snowColor = ["#F1F1F1", "#EDEDEDbf"]; // Snowflake colors
   const snowEntity = "&#x2022;"; // Snowflake character
   const snowSpeed = 0.5; // Base falling speed
   const snowMinSize = 15; // Minimum snowflake size (px)
   const snowMaxSize = 28; // Maximum snowflake size (px)
   const snowStyles = "cursor: default; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; -o-user-select: none; user-select: none;";

   // --- Internal state ---
   let snow = []; // Stores snowflake elements
   let pos = []; // Used for horizontal sway calculations
   let coords = []; // Used for horizontal sway calculations
   let lefr = []; // Horizontal sway amplitude
   let marginBottom, marginRight; // Snowfall area boundaries

   /**
    * Generate a random number within a range.
    * @param {number} range - Upper limit (exclusive).
    * @returns {number} Random integer.
    */
   function randomise(range) {
      return Math.floor(range * Math.random());
   }

   /**
    * Initialize the snowflakes.
    */
    function initSnow() {
      const snowSizeRange = snowMaxSize - snowMinSize;
      marginBottom = window.innerHeight - 5;
      marginRight = window.innerWidth - 15;

      const snowContainer = document.body;

      for (let i = 0; i <= snowMax; i++) {
         const flake = document.createElement("span");
         flake.innerHTML = snowEntity;

         // Apply styles from the snowStyles string.
         flake.setAttribute('style', snowStyles);

         // Apply the positioning and display styles.
         flake.style.position = "fixed";
         flake.style.fontFamily = "inherit";
         flake.style.zIndex = 999999;
         flake.style.pointerEvents = "none"; // Ensure clicks pass through

         // Calculate and apply random snowflake properties.
         const currentFlakeSize = randomise(snowSizeRange) + snowMinSize;
         flake.style.fontSize = currentFlakeSize + "px";
         flake.style.color = snowColor[randomise(snowColor.length)];

         // Store custom properties directly on the snowflake DOM object.
         flake.size = currentFlakeSize;
         flake.sink = (snowSpeed * flake.size) / 20; // Falling speed based on size
         flake.posX = randomise(marginRight - flake.size); // Initial X position
         flake.posY = randomise(marginBottom - 2 * flake.size);

         flake.style.left = flake.posX + "px";
         flake.style.top = flake.posY + "px";

         // Store animation data.
         snow[i] = flake;
         coords[i] = 0; // Initial angle for sine sway
         lefr[i] = Math.random() * 10; // Horizontal sway amplitude
         pos[i] = 0.02 + Math.random() / 100; // Horizontal sway frequency/speed

         snowContainer.appendChild(flake); // Add the snowflake to the page
      }
      moveSnow(); // Start the animation
   }

   /**
    * Update dimensions when the window changes.
    */
   function resize() {
      marginBottom = window.innerHeight - 5;
      marginRight = window.innerWidth - 15;
   }

   /**
    * Move the snowflakes (animation loop).
    */
   function moveSnow() {
      for (let i = 0; i <= snowMax; i++) {
         const flake = snow[i];
         if (!flake) continue; // Skip missing snowflakes

         coords[i] += pos[i]; // Update the sway angle
         flake.posY += flake.sink; // Move the snowflake down vertically

         // Calculate the new X position with horizontal sway.
         const currentX = flake.posX + lefr[i] * Math.sin(coords[i]);
         flake.style.left = currentX + "px";
         flake.style.top = flake.posY + "px";

         // Reset the snowflake to the top if it leaves the screen at the bottom or right edge.
         if (
            flake.posY >= marginBottom || // Beyond the bottom edge completely
            currentX > marginRight // Beyond the right edge
         ) {
            flake.posX = randomise(marginRight - flake.size); // New random X position
            flake.posY = -flake.size; // Reset to above the top edge
         }
      }
      requestAnimationFrame(moveSnow); // Use requestAnimationFrame for smoother animation
   }

   // Attach events.
   window.addEventListener("resize", resize);
   window.addEventListener("load", initSnow);

})();
