/* script.js */
$(document).ready(function() {
    // 1. Initialize DataTables with customized settings
    $('.datatable').DataTable({
        pageLength: 10, // Increased default
        lengthMenu: [5, 10, 25, 50],
        order: [[0, 'desc']],
        language: {
              paginate: {
                  previous: '<i class="fa fa-chevron-left"></i>',
                  next: '<i class="fa fa-chevron-right"></i>'
              }
          }
      });

    // 2. Initialize Toast
    var toastEl = document.getElementById('liveToast');
    if (toastEl) new bootstrap.Toast(toastEl).show();

    // 3. Populate Edit Book Modal
    $(document).on('click', '.edit-book-btn', function() {
        const d = $(this).data();
        $('#edit_book_id').val(d.id);
        $('#edit_book_title').val(d.title);
        $('#edit_book_author').val(d.author);
        $('#edit_book_publisher').val(d.publisher);
        $('#edit_book_year').val(d.year);
        $('#edit_book_genre').val(d.genre);
    });

    // 4. Populate Edit Member Modal
    $(document).on('click', '.edit-member-btn', function() {
        const d = $(this).data();
        $('#edit_member_id').val(d.id);
        $('#edit_member_name').val(d.name);
        $('#edit_member_email').val(d.email);
        $('#edit_member_phone').val(d.phone);
        $('#edit_member_address').val(d.address);
    });

    // Populate Edit Loan Modal
    $(document).on('click', '.edit-loan-btn', function() {
        const d = $(this).data();
        $('#edit_loan_id').val(d.id);
        $('#edit_loan_date').val(d.loandate);
        $('#edit_loan_return').val(d.returndate);
    });

    // Loading State on Submit
    $('form').on('submit', function() {
        // Hide modals
        $('.modal').modal('hide');
        // Show loading overlay
        $('#loadingOverlay').addClass('active');
    });
});