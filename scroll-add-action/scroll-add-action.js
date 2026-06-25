document.addEventListener('DOMContentLoaded', function() {
   handleAddAction(scrollAddAction.scrollAddActionValue);
});

function handleAddAction(ClassAction) {

   const filter_gray = document.querySelectorAll('.' + ClassAction + '');
   const elementInView = (el, dividend = 1) => {
      const elementTop = el.getBoundingClientRect().top;
      return (
         elementTop <= (window.innerHeight || document.documentElement.clientHeight) / dividend
      );
   };

   const elementOutofView = (el) => {
      const elementTop = el.getBoundingClientRect().top;
      return (
         elementTop > ((window.innerHeight || document.documentElement.clientHeight) - 90)
      );
   };

   const displayScrollElement = (element) => {
      element.classList.add('action');
   };

   const hideScrollElement = (element) => {
      element.classList.remove('action');
   };

   const handleScrollAnimation = (elements) => {
      elements.forEach((el) => {
         if (elementInView(el, 1.5)) {
            displayScrollElement(el);
         } else if (elementOutofView(el)) {
            hideScrollElement(el);
         }
      });
   };

   window.addEventListener('scroll', () => {
      handleScrollAnimation(filter_gray);
   });

}