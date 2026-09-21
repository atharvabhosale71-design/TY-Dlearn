<?php
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="section-bg" style="padding: 80px 0;">
    <div class="container">

        <div class="section-title text-center">

            <span class="section-tag">FAQ</span>

            <h2>
                Frequently Asked Questions
            </h2>

            <p>
                Find answers to common questions about DLearn,
                dyslexia screening and learning support.
            </p>

        </div>


        <div class="row justify-content-center mt-5">

            <div class="col-lg-9">

                <div class="faq-list">


                    <!-- FAQ 1 -->

                    <div class="faq-item">

                        <button class="faq-question" type="button">

                            <span>
                                What is DLearn?
                            </span>

                            <span class="faq-icon">
                                +
                            </span>

                        </button>


                        <div class="faq-answer">

                            <p>
                                DLearn is a web-based platform designed
                                for dyslexia awareness, screening,
                                assessment and learning support.
                            </p>

                        </div>

                    </div>


                    <!-- FAQ 2 -->

                    <div class="faq-item">

                        <button class="faq-question" type="button">

                            <span>
                                What is dyslexia?
                            </span>

                            <span class="faq-icon">
                                +
                            </span>

                        </button>


                        <div class="faq-answer">

                            <p>
                                Dyslexia is a learning difficulty that
                                can affect reading, spelling, writing
                                and language-related skills.
                            </p>

                        </div>

                    </div>


                    <!-- FAQ 3 -->

                    <div class="faq-item">

                        <button class="faq-question" type="button">

                            <span>
                                Who can use DLearn?
                            </span>

                            <span class="faq-icon">
                                +
                            </span>

                        </button>


                        <div class="faq-answer">

                            <p>
                                DLearn is designed to support parents,
                                teachers and administrators in the
                                screening and learning support process.
                            </p>

                        </div>

                    </div>


                    <!-- FAQ 4 -->

                    <div class="faq-item">

                        <button class="faq-question" type="button">

                            <span>
                                Does DLearn provide a medical diagnosis?
                            </span>

                            <span class="faq-icon">
                                +
                            </span>

                        </button>


                        <div class="faq-answer">

                            <p>
                                No. DLearn is intended as an educational
                                screening and learning support platform.
                                It should not be considered a replacement
                                for professional diagnosis.
                            </p>

                        </div>

                    </div>


                    <!-- FAQ 5 -->

                    <div class="faq-item">

                        <button class="faq-question" type="button">

                            <span>
                                Can parents track learning progress?
                            </span>

                            <span class="faq-icon">
                                +
                            </span>

                        </button>


                        <div class="faq-answer">

                            <p>
                                The planned DLearn system includes
                                progress tracking through assessments,
                                learning activities and performance
                                records.
                            </p>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>
</section>


<script>

document.querySelectorAll(".faq-question").forEach(function(button) {

    button.addEventListener("click", function() {

        const currentItem = this.parentElement;
        const currentAnswer = currentItem.querySelector(".faq-answer");
        const currentIcon = currentItem.querySelector(".faq-icon");


        /*
         * Close other FAQ answers
         */

        document.querySelectorAll(".faq-item").forEach(function(item) {

            if (item !== currentItem) {

                item.classList.remove("active");

                item.querySelector(".faq-answer").style.display = "none";

                item.querySelector(".faq-icon").textContent = "+";

            }

        });


        /*
         * Open / Close current answer
         */

        if (currentItem.classList.contains("active")) {

            currentItem.classList.remove("active");

            currentAnswer.style.display = "none";

            currentIcon.textContent = "+";

        } else {

            currentItem.classList.add("active");

            currentAnswer.style.display = "block";

            currentIcon.textContent = "−";

        }

    });

});

</script>


<?php
include 'includes/footer.php';
?>