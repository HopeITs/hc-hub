<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Support\Employees\HrMedia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The signed copy of a contract amendment, from the private side of the HR
 * disk. It carries the pay clause whenever pay changed, so the route holds
 * it to the sheet's own rule (ContractAmendmentPolicy::exportAmendment).
 */
class ContractAmendmentSignedController extends Controller
{
    public const COLLECTION = 'signed';

    /** What a signed copy may be: a scan or a photo of the sheet. */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public function __invoke(Contract $contract, ContractAmendment $amendment): Response
    {
        $file = HrMedia::for(HrMedia::CONTRACT_AMENDMENT, (int) $amendment->id, self::COLLECTION)->last();

        abort_if($file === null || ! in_array(HrMedia::extension($file), self::ALLOWED_EXTENSIONS, true), 404);

        return HrMedia::response($file);
    }
}
