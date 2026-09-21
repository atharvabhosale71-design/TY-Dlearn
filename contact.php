<?php
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="section-bg" style="padding: 80px 0;">
    <div class="container">

        <div class="section-title text-center">

            <span class="section-tag">CONTACT US</span>

            <h2>
                Get In Touch With DLearn
            </h2>

            <p>
                Have a question about DLearn or need more information?
                Send us a message.
            </p>

        </div>

        <div class="row justify-content-center mt-5">

            <div class="col-lg-5 mb-4">

                <div class="bg-white p-4 rounded shadow-sm h-100">

                    <h3>Contact DLearn</h3>

                    <p>
                        We are working to make dyslexia awareness,
                        screening and learning support more accessible
                        through a simple web-based platform.
                    </p>

                    <div class="mt-4">

                        <h5>Support</h5>
                        <p>
                            For questions regarding the DLearn platform,
                            you can use the contact form.
                        </p>

                    </div>

                    <div class="mt-4">

                        <h5>Project</h5>
                        <p>
                            DLearn is a final-year B.Sc. IT project
                            focused on dyslexia screening and learning
                            support.
                        </p>

                    </div>

                </div>

            </div>

            <div class="col-lg-6">

                <div class="bg-white p-4 rounded shadow-sm">

                    <h3 class="mb-4">Send Us a Message</h3>

                    <form action="#" method="post">

                        <div class="mb-3">

                            <label class="form-label">
                                Name
                            </label>

                            <input type="text"
                                   class="form-control"
                                   name="name"
                                   placeholder="Enter your name"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input type="email"
                                   class="form-control"
                                   name="email"
                                   placeholder="Enter your email"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Subject
                            </label>

                            <input type="text"
                                   class="form-control"
                                   name="subject"
                                   placeholder="Enter subject"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Message
                            </label>

                            <textarea class="form-control"
                                      name="message"
                                      rows="5"
                                      placeholder="Write your message..."
                                      required></textarea>

                        </div>

                        <button type="submit"
                                class="btn btn-orange">
                            Send Message
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>
</section>

<?php
include 'includes/footer.php';
?>